<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ApplicationPackageService
{
    protected string $packagePath;

    public function __construct()
    {
        $this->packagePath = storage_path('app/packages');
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, detail: string}>
     */
    public function preflightChecks(): array
    {
        $npm = $this->npmExecutable();
        $node = $this->nodeExecutable();
        $electronPath = $this->resolveElectronProjectPath();
        $processTemp = $this->ensureProcessTempDirectory();

        return [
            $this->check(
                'zip',
                'ZIP extension',
                extension_loaded('zip'),
                extension_loaded('zip')
                    ? 'Available'
                    : 'PHP ZipArchive extension is required. Enable extension=zip in '.PHP_BINARY,
            ),
            $this->check('writable', 'Package storage', is_writable(storage_path('app')) || File::isDirectory($this->packagePath), 'storage/app must be writable.'),
            $this->check('vendor', 'Dependencies', is_dir(base_path('vendor')), 'Run composer install before packaging.'),
            $this->check('assets', 'Frontend build', is_dir(public_path('build')), 'Run npm run build or use Build Assets below.'),
            $this->check('node', 'Node.js', $node !== null, $node ? "Found at {$node}" : 'Set NODE_BINARY in .env if builds fail from the web UI.'),
            $this->check('npm', 'NPM', $npm !== null, $npm ? "Found at {$npm}" : 'Set NPM_BINARY in .env if builds fail from the web UI.'),
            $this->check(
                'electron',
                'Electron project',
                $electronPath !== null,
                $electronPath !== null
                    ? "Found at {$electronPath}"
                    : 'The electron/ folder is missing. Build the installer from the full development project, not an installed copy of the app.',
            ),
            $this->check(
                'process_temp',
                'Process temp directory',
                is_writable($processTemp),
                "Using {$processTemp}",
            ),
            $this->check(
                'desktop_icon',
                'Desktop app icon',
                $this->resolveDesktopIconRelativePath() !== null,
                $this->resolveDesktopIconRelativePath() !== null
                    ? 'Custom icon will be used in the Windows installer.'
                    : 'Upload a PNG or ICO icon (256x256 or larger) before building the installer.',
            ),
        ];
    }

    /**
     * @return array{filename: string, size: int, updated_at: string, preview_url: string}|null
     */
    public function desktopIconInfo(): ?array
    {
        $path = $this->resolveDesktopIconAbsolutePath();

        if ($path === null) {
            return null;
        }

        return [
            'filename' => basename($path),
            'size' => File::size($path),
            'updated_at' => date('c', File::lastModified($path)),
            'preview_url' => route('settings.application-package.icon'),
        ];
    }

    /**
     * @param  \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|\Illuminate\Http\UploadedFile  $file
     * @return array{filename: string, size: int, updated_at: string, preview_url: string}
     */
    public function storeDesktopIcon($file): array
    {
        $this->ensureDesktopAssetsDirectory();

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['png', 'ico', 'jpg', 'jpeg'], true)) {
            throw new RuntimeException('Desktop icon must be a PNG, JPG, or ICO file.');
        }

        $targetName = $extension === 'ico' ? 'icon.ico' : 'icon.png';
        $targetPath = $this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.$targetName;

        File::put($targetPath, file_get_contents($file->getRealPath()));

        if ($targetName === 'icon.png') {
            $this->assertMinimumIconDimensions($targetPath);
        }

        $this->removeUnusedDesktopIconVariants($targetName);
        $this->syncElectronBuilderIconConfig();

        return $this->desktopIconInfo() ?? throw new RuntimeException('Unable to save desktop icon.');
    }

    /**
     * @return array{filename: string, size: int, updated_at: string, preview_url: string}
     */
    public function copySchoolLogoAsDesktopIcon(string $sourcePath): array
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('Upload a school logo in General Settings first.');
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            throw new RuntimeException('SVG logos cannot be used as desktop icons. Upload a PNG or ICO file instead.');
        }

        $this->ensureDesktopAssetsDirectory();
        $targetPath = $this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.'icon.png';

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            if (! function_exists('imagecreatefromjpeg')) {
                throw new RuntimeException('PHP GD extension is required to convert the school logo into a desktop icon.');
            }

            $image = imagecreatefromjpeg($sourcePath);

            if ($image === false) {
                throw new RuntimeException('Unable to read the school logo file.');
            }

            imagepng($image, $targetPath);
            imagedestroy($image);
        } else {
            File::copy($sourcePath, $targetPath);
        }

        $this->assertMinimumIconDimensions($targetPath);
        $this->removeUnusedDesktopIconVariants('icon.png');
        $this->syncElectronBuilderIconConfig();

        return $this->desktopIconInfo() ?? throw new RuntimeException('Unable to copy school logo as desktop icon.');
    }

    public function removeDesktopIcon(): void
    {
        foreach (['icon.png', 'icon.ico'] as $filename) {
            $path = $this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.$filename;

            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $this->syncElectronBuilderIconConfig();
    }

    public function resolveDesktopIconAbsolutePath(): ?string
    {
        foreach (['icon.ico', 'icon.png'] as $filename) {
            $path = $this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.$filename;

            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function resolveDesktopIconRelativePath(): ?string
    {
        if (File::exists($this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.'icon.ico')) {
            return 'assets/icon.ico';
        }

        if (File::exists($this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.'icon.png')) {
            return 'assets/icon.png';
        }

        return null;
    }

    protected function desktopAssetsDirectory(): string
    {
        return base_path('electron'.DIRECTORY_SEPARATOR.'assets');
    }

    protected function ensureDesktopAssetsDirectory(): void
    {
        if (! File::isDirectory($this->desktopAssetsDirectory())) {
            File::makeDirectory($this->desktopAssetsDirectory(), 0755, true);
        }
    }

    protected function removeUnusedDesktopIconVariants(string $activeFilename): void
    {
        foreach (['icon.png', 'icon.ico'] as $filename) {
            if ($filename === $activeFilename) {
                continue;
            }

            $path = $this->desktopAssetsDirectory().DIRECTORY_SEPARATOR.$filename;

            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    protected function assertMinimumIconDimensions(string $path): void
    {
        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            File::delete($path);

            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        if ($dimensions[0] < 256 || $dimensions[1] < 256) {
            File::delete($path);

            throw new RuntimeException('Desktop icon must be at least 256x256 pixels.');
        }
    }

    protected function syncElectronBuilderIconConfig(): void
    {
        $packageJsonPath = base_path('electron'.DIRECTORY_SEPARATOR.'package.json');

        if (! File::exists($packageJsonPath)) {
            return;
        }

        /** @var array<string, mixed> $package */
        $package = json_decode(File::get($packageJsonPath), true, 512, JSON_THROW_ON_ERROR);

        $icon = $this->resolveDesktopIconRelativePath();

        if ($icon !== null) {
            $package['build']['win']['icon'] = $icon;
        } else {
            $package['build']['win']['icon'] = 'assets/icon.png';
        }

        File::put(
            $packageJsonPath,
            json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }

    public function canPackage(): bool
    {
        return extension_loaded('zip')
            && is_dir(base_path('vendor'))
            && (is_writable(storage_path('app')) || File::isDirectory($this->packagePath));
    }

    public function canBuildDesktop(): bool
    {
        return $this->resolveElectronProjectPath() !== null
            && $this->npmExecutable() !== null
            && is_writable($this->ensureProcessTempDirectory());
    }

    /**
     * @return array{success: bool, output: string}
     */
    public function buildAssets(): array
    {
        $this->ensurePackageDirectory();
        $this->ensureProcessTempDirectory();

        $result = $this->runNodeCommand(base_path(), $this->npmCommand().' run build', 300);

        return [
            'success' => $result->successful(),
            'output' => trim($result->output()."\n".$result->errorOutput()),
        ];
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    public function createDeployPackage(): array
    {
        if (! $this->canPackage()) {
            throw new RuntimeException('System requirements for packaging are not met.');
        }

        $this->ensurePackageDirectory();

        $version = (string) config('classsync.version', '1.0.0');
        $filename = sprintf('class-sync-application-%s-%s.zip', $version, now()->format('Ymd_His'));
        $finalPath = $this->packagePath.DIRECTORY_SEPARATOR.$filename;

        if ($this->canUseTar()) {
            $this->createDeployPackageWithTar($finalPath);

            return $this->packageMeta($filename, $finalPath);
        }

        return $this->createDeployPackageWithZipArchive($filename, $finalPath);
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    protected function createDeployPackageWithZipArchive(string $filename, string $finalPath): array
    {
        $tempDirectory = $this->ensureProcessTempDirectory();
        $workingPath = tempnam($tempDirectory, 'classsync_pkg_');

        if ($workingPath === false) {
            throw new RuntimeException('Unable to create a temporary package file.');
        }

        @unlink($workingPath);
        $workingPath .= '.zip';

        $zip = new ZipArchive;

        if ($zip->open($workingPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create application package.');
        }

        $zip->addFromString('DEPLOYMENT_README.txt', $this->deploymentReadme());

        foreach ($this->packageableFiles() as $file) {
            $relativePath = $this->relativePath($file);

            if ($relativePath === null || $this->shouldExclude($relativePath)) {
                continue;
            }

            $archivePath = str_replace('\\', '/', $relativePath);
            $contents = @file_get_contents($file);

            if ($contents !== false) {
                $zip->addFromString($archivePath, $contents);
            }
        }

        if (! $zip->close()) {
            @unlink($workingPath);

            throw new RuntimeException('Unable to finalize application package.');
        }

        if (File::exists($finalPath)) {
            File::delete($finalPath);
        }

        File::move($workingPath, $finalPath);

        return $this->packageMeta($filename, $finalPath);
    }

    protected function createDeployPackageWithTar(string $finalPath): void
    {
        $this->ensureProcessTempDirectory();

        $readmePath = base_path('DEPLOYMENT_README.txt');
        File::put($readmePath, $this->deploymentReadme());

        $command = ['tar', '-acf', $finalPath];

        foreach ($this->excludedRelativePaths() as $excluded) {
            $command[] = '--exclude='.$excluded;
        }

        $command[] = '-C';
        $command[] = base_path();

        foreach (scandir(base_path()) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $relativePath = str_replace('\\', '/', $entry);

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $command[] = $entry;
        }

        $result = Process::path(base_path())
            ->timeout(600)
            ->env($this->nodeProcessEnvironment())
            ->run($command);

        File::delete($readmePath);

        if (! $result->successful() || ! File::exists($finalPath)) {
            throw new RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'Unable to create application package.'));
        }

        if (File::size($finalPath) < 1024) {
            throw new RuntimeException('Application package was created but appears to be empty.');
        }
    }

    protected function canUseTar(): bool
    {
        return Process::run(['tar', '--version'])->successful();
    }

    /**
     * @return array{success: bool, output: string, package?: array{filename: string, path: string, size: int, created_at: string}}
     */
    public function buildDesktopInstaller(): array
    {
        $this->ensureProcessTempDirectory();

        $electronPath = $this->resolveElectronProjectPath();

        if ($electronPath === null) {
            throw new RuntimeException(
                'Electron project folder was not found at '.base_path('electron').'. '
                .'Build the installer from the full development project (with the electron/ folder), not from an installed copy of Class Sync.',
            );
        }

        $npm = $this->requireNpmExecutable();

        $this->ensurePackageDirectory();

        $install = $this->ensureElectronDependencies($electronPath);

        if (! $install['success']) {
            return [
                'success' => false,
                'output' => $install['output'],
            ];
        }

        $prefetch = $this->prefetchElectronBinary($electronPath);

        if (! $prefetch['success']) {
            return [
                'success' => false,
                'output' => $prefetch['output'],
            ];
        }

        $buildTimeout = (int) config('classsync.deployment.electron_build_timeout', 7200);

        $build = $this->runNodeCommand(
            $electronPath,
            $this->quoteExecutable($npm).' run build:desktop',
            $buildTimeout,
        );

        $output = trim($prefetch['output']."\n".$build->output()."\n".$build->errorOutput());

        if (! $build->successful()) {
            return [
                'success' => false,
                'output' => $output,
            ];
        }

        $installer = $this->findLatestDesktopInstaller();

        if ($installer === null) {
            return [
                'success' => false,
                'output' => $output."\n\nNo installer artifact was found in electron/dist or electron/dist-new.",
            ];
        }

        $targetName = basename($installer);
        $targetPath = $this->packagePath.DIRECTORY_SEPARATOR.$targetName;

        File::copy($installer, $targetPath);

        return [
            'success' => true,
            'output' => $output,
            'package' => $this->packageMeta($targetName, $targetPath),
        ];
    }

    /**
     * @return array<int, array{filename: string, path: string, size: int, created_at: string, type: string, source: string}>
     */
    public function list(): array
    {
        $this->ensurePackageDirectory();

        $packages = collect(File::files($this->packagePath))
            ->map(fn ($file) => array_merge(
                $this->packageMeta($file->getFilename(), $file->getPathname()),
                [
                    'type' => $this->packageType($file->getFilename()),
                    'source' => 'storage',
                ],
            ));

        $knownFilenames = $packages->pluck('filename');

        foreach ($this->findDesktopInstallers() as $installerPath) {
            $filename = basename($installerPath);

            if ($knownFilenames->contains($filename)) {
                continue;
            }

            $packages->push(array_merge(
                $this->packageMeta($filename, $installerPath),
                [
                    'type' => 'desktop',
                    'source' => 'electron',
                ],
            ));
        }

        return $packages
            ->sortByDesc(fn (array $package) => strtotime($package['created_at']))
            ->values()
            ->all();
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string, type: string, source: string}|null
     */
    public function latestDesktopInstaller(): ?array
    {
        $installer = $this->findLatestDesktopInstaller();

        if ($installer === null) {
            return null;
        }

        $filename = basename($installer);
        $storedPath = $this->resolvePackagePath($filename);

        if (File::exists($storedPath)) {
            return array_merge(
                $this->packageMeta($filename, $storedPath),
                ['type' => 'desktop', 'source' => 'storage'],
            );
        }

        return array_merge(
            $this->packageMeta($filename, $installer),
            ['type' => 'desktop', 'source' => 'electron'],
        );
    }

    /**
     * Copy a desktop installer into storage/app/packages for download hosting.
     *
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    public function importDesktopInstaller(string $filename): array
    {
        $this->ensurePackageDirectory();

        $sourcePath = $this->resolveDownloadPath($filename);
        $targetPath = $this->resolvePackagePath(basename($filename));

        if (! File::exists($targetPath) || File::lastModified($sourcePath) > File::lastModified($targetPath)) {
            File::copy($sourcePath, $targetPath);
        }

        return $this->packageMeta(basename($filename), $targetPath);
    }

    public function delete(string $filename): bool
    {
        $path = $this->resolvePackagePath($filename);

        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    public function downloadResponse(string $filename): BinaryFileResponse
    {
        $path = $this->resolveDownloadPath($filename);

        return response()->download($path, basename($filename));
    }

    protected function resolveDownloadPath(string $filename): string
    {
        $basename = basename($filename);

        if ($basename !== $filename) {
            abort(404);
        }

        if (! $this->isDownloadableFilename($basename)) {
            abort(404);
        }

        $storedPath = $this->resolvePackagePath($basename);

        if (File::exists($storedPath)) {
            return $storedPath;
        }

        foreach ($this->findDesktopInstallers() as $installerPath) {
            if (basename($installerPath) === $basename) {
                return $installerPath;
            }
        }

        abort(404);
    }

    protected function isDownloadableFilename(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, ['zip', 'exe', 'msi'], true);
    }

    /**
     * @return list<string>
     */
    protected function excludedRelativePaths(): array
    {
        return [
            '.git',
            'node_modules',
            'electron/node_modules',
            'electron/dist',
            'storage/app/packages',
            'storage/logs',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/framework/testing',
            'tests',
            '.env',
            '.phpunit.result.cache',
            '.phpunit.cache',
            'database/database.sqlite',
            'database/testing.sqlite',
        ];
    }

    /**
     * @return list<string>
     */
    protected function packageableFiles(): array
    {
        $files = [];

        foreach (File::allFiles(base_path()) as $file) {
            $files[] = $file->getPathname();
        }

        foreach (File::directories(base_path()) as $directory) {
            $relative = $this->relativePath($directory);

            if ($relative !== null && ! $this->shouldExclude($relative)) {
                $gitkeep = $directory.DIRECTORY_SEPARATOR.'.gitkeep';

                if (! File::exists($gitkeep) && $this->isEmptyDirectory($directory)) {
                    continue;
                }
            }
        }

        return $files;
    }

    protected function shouldExclude(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);

        foreach ($this->excludedRelativePaths() as $excluded) {
            if ($normalized === $excluded || str_starts_with($normalized, $excluded.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function relativePath(string $absolutePath): ?string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($absolutePath, $base)) {
            return null;
        }

        return ltrim(substr($absolutePath, strlen($base)), DIRECTORY_SEPARATOR);
    }

    protected function deploymentReadme(): string
    {
        $version = (string) config('classsync.version', '1.0.0');

        return <<<TXT
Class Sync Application Package v{$version}
=========================================

This archive contains a production-ready copy of Class Sync.

LAN / Server deployment
-----------------------
1. Extract this archive on the school server.
2. Copy .env.example to .env and configure database settings.
3. Run: php artisan key:generate
4. Open /setup in a browser or run: php artisan migrate --seed
5. Point Apache/Nginx to the public/ directory, or run:
   php artisan serve --host=0.0.0.0 --port=8000

Desktop application (Windows)
-----------------------------
1. On the build machine:
   cd electron
   npm install
   npm run build:desktop
2. Install the setup file from electron/dist/.
3. Open "Class Sync" from the Start Menu.
4. Complete /setup on first launch if prompted.

The desktop app bundles PHP and Laravel. It runs locally at http://127.0.0.1:8000
inside a native window — no browser required.

Notes
-----
- .env is intentionally excluded. Use .env.example as your template.
- Uploaded files are not included. Restore those separately if needed.
- For updates, replace the application files and run php artisan migrate.

TXT;
    }

    protected function ensurePackageDirectory(): void
    {
        if (! File::isDirectory($this->packagePath)) {
            File::makeDirectory($this->packagePath, 0755, true);
        }
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    protected function packageMeta(string $filename, string $path): array
    {
        return [
            'filename' => $filename,
            'path' => $path,
            'size' => File::exists($path) ? File::size($path) : 0,
            'created_at' => File::exists($path) ? date('c', File::lastModified($path)) : now()->toIso8601String(),
        ];
    }

    protected function resolvePackagePath(string $filename): string
    {
        $basename = basename($filename);

        if ($basename !== $filename) {
            throw new RuntimeException('Invalid package filename.');
        }

        return $this->packagePath.DIRECTORY_SEPARATOR.$basename;
    }

    protected function packageType(string $filename): string
    {
        if (str_ends_with(strtolower($filename), '.zip')) {
            return 'deploy';
        }

        if (str_ends_with(strtolower($filename), '.exe')) {
            return 'desktop';
        }

        return 'artifact';
    }

    protected function findLatestDesktopInstaller(): ?string
    {
        return collect($this->findDesktopInstallers())
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->first();
    }

    /**
     * @return list<string>
     */
    protected function findDesktopInstallers(): array
    {
        $installers = [];

        foreach ($this->desktopOutputDirectories() as $directory) {
            foreach ($this->findDesktopInstallerFilesInDirectory($directory) as $installer) {
                $installers[] = $installer;
            }
        }

        return array_values(array_unique($installers));
    }

    /**
     * @return list<string>
     */
    protected function desktopOutputDirectories(): array
    {
        $electronPath = base_path('electron');

        return array_values(array_filter([
            $electronPath.DIRECTORY_SEPARATOR.'dist',
            $electronPath.DIRECTORY_SEPARATOR.'dist-new',
        ], fn (string $directory) => is_dir($directory)));
    }

    /**
     * @return list<string>
     */
    protected function findDesktopInstallerFilesInDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(function ($file) {
                $extension = strtolower($file->getExtension());

                return in_array($extension, ['exe', 'msi'], true)
                    && str_contains(strtolower($file->getFilename()), 'setup');
            })
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    protected function findLatestDesktopArtifact(string $directory): ?string
    {
        $installers = $this->findDesktopInstallerFilesInDirectory($directory);

        if ($installers === []) {
            return null;
        }

        return collect($installers)
            ->sortByDesc(fn (string $path) => filemtime($path))
            ->first();
    }

    protected function isEmptyDirectory(string $directory): bool
    {
        return count(scandir($directory) ?: []) <= 2;
    }

    /**
     * @return array{key: string, label: string, ok: bool, detail: string}
     */
    protected function check(string $key, string $label, bool $ok, string $detail): array
    {
        return compact('key', 'label', 'ok', 'detail');
    }

    protected function commandExists(string $command): bool
    {
        $result = Process::run($this->command($command).' --version');

        return $result->successful();
    }

    protected function command(string $command): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $command.'.cmd';
        }

        return $command;
    }

    protected function npmCommand(): string
    {
        return $this->quoteExecutable($this->requireNpmExecutable());
    }

    protected function requireNpmExecutable(): string
    {
        $npm = $this->npmExecutable();

        if ($npm === null) {
            throw new RuntimeException(
                'NPM was not found. Install Node.js or set NPM_BINARY in .env (for example: C:\\Program Files\\nodejs\\npm.cmd).',
            );
        }

        return $npm;
    }

    protected function npmExecutable(): ?string
    {
        return $this->resolveExecutable('npm', [
            config('classsync.deployment.npm_binary'),
            'C:\\Program Files\\nodejs\\npm.cmd',
            ...$this->laragonNodeToolPaths('npm.cmd'),
        ]);
    }

    protected function nodeExecutable(): ?string
    {
        return $this->resolveExecutable('node', [
            config('classsync.deployment.node_binary'),
            'C:\\Program Files\\nodejs\\node.exe',
            ...$this->laragonNodeToolPaths('node.exe'),
        ]);
    }

    /**
     * @param  list<string|null>  $candidates
     */
    protected function resolveExecutable(string $command, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        if ($this->commandExists($command)) {
            return $this->command($command);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function laragonNodeToolPaths(string $binary): array
    {
        $paths = glob('C:\\laragon\\bin\\nodejs\\node-*\\'.$binary) ?: [];

        rsort($paths);

        return $paths;
    }

    /**
     * @return array<string, string>
     */
    protected function nodeProcessEnvironment(): array
    {
        $pathEntries = [];

        foreach ([$this->npmExecutable(), $this->nodeExecutable()] as $executable) {
            if (is_string($executable)) {
                $pathEntries[] = dirname($executable);
            }
        }

        $pathEntries = array_merge($pathEntries, [
            'C:\\Program Files\\nodejs',
            ...array_map(fn (string $path) => dirname($path), $this->laragonNodeToolPaths('node.exe')),
        ]);

        $existingPath = getenv('Path') ?: getenv('PATH') ?: '';
        $mergedPath = implode(';', array_unique(array_filter([...$pathEntries, $existingPath])));

        $tempDirectory = $this->ensureProcessTempDirectory();
        $electronCache = storage_path('app'.DIRECTORY_SEPARATOR.'electron-cache');

        if (! File::isDirectory($electronCache)) {
            File::makeDirectory($electronCache, 0755, true);
        }

        $environment = array_filter([
            'PATH' => $mergedPath,
            'Path' => $mergedPath,
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'USERPROFILE' => $this->windowsEnv('USERPROFILE'),
            'LOCALAPPDATA' => $this->windowsLocalAppData(),
            'APPDATA' => $this->windowsEnv('APPDATA'),
            'HOMEDRIVE' => $this->windowsEnv('HOMEDRIVE') ?? 'C:',
            'HOMEPATH' => $this->windowsEnv('HOMEPATH'),
            'SystemRoot' => $this->windowsEnv('SystemRoot') ?? 'C:\\Windows',
            'COMSPEC' => $this->windowsEnv('COMSPEC') ?? 'C:\\Windows\\System32\\cmd.exe',
            'ELECTRON_CACHE' => $electronCache,
            'electron_config_cache' => $electronCache,
        ], fn (?string $value) => is_string($value) && $value !== '');

        $mirror = config('classsync.deployment.electron_mirror');

        if (is_string($mirror) && $mirror !== '') {
            $environment['ELECTRON_MIRROR'] = rtrim($mirror, '/').'/';
        }

        return $environment;
    }

    protected function windowsEnv(string $key): ?string
    {
        $value = getenv($key);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        $serverValue = $_SERVER[$key] ?? null;

        if (is_string($serverValue) && $serverValue !== '') {
            return $serverValue;
        }

        return null;
    }

    protected function windowsLocalAppData(): string
    {
        if ($localAppData = $this->windowsEnv('LOCALAPPDATA')) {
            return $localAppData;
        }

        if ($userProfile = $this->windowsEnv('USERPROFILE')) {
            return $userProfile.'\\AppData\\Local';
        }

        return sys_get_temp_dir();
    }

    protected function runNodeCommand(string $cwd, string $command, int $timeout): \Illuminate\Process\ProcessResult
    {
        $this->ensureProcessTempDirectory();

        return Process::path($cwd)
            ->timeout($timeout)
            ->env($this->nodeProcessEnvironment())
            ->run($command);
    }

    protected function ensureProcessTempDirectory(): string
    {
        $tempDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'process-temp');

        if (! File::isDirectory($tempDirectory)) {
            File::makeDirectory($tempDirectory, 0755, true);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            putenv('TEMP='.$tempDirectory);
            putenv('TMP='.$tempDirectory);
            $_ENV['TEMP'] = $tempDirectory;
            $_ENV['TMP'] = $tempDirectory;
            $_SERVER['TEMP'] = $tempDirectory;
            $_SERVER['TMP'] = $tempDirectory;
        }

        return $tempDirectory;
    }

    protected function resolveElectronProjectPath(): ?string
    {
        $electronPath = base_path('electron');
        $packageJson = $electronPath.DIRECTORY_SEPARATOR.'package.json';

        if (! is_dir($electronPath) || ! is_file($packageJson)) {
            return null;
        }

        return $electronPath;
    }

    protected function quoteExecutable(string $path): string
    {
        return str_contains($path, ' ') ? '"'.$path.'"' : $path;
    }

    /**
     * @return array{success: bool, output: string}
     */
    protected function prefetchElectronBinary(string $electronPath): array
    {
        $installScript = $electronPath.DIRECTORY_SEPARATOR.'node_modules'
            .DIRECTORY_SEPARATOR.'electron'
            .DIRECTORY_SEPARATOR.'install.js';

        if (! is_file($installScript)) {
            return [
                'success' => true,
                'output' => '',
            ];
        }

        $node = $this->nodeExecutable() ?? 'node';
        $timeout = (int) config('classsync.deployment.electron_download_timeout', 7200);

        $result = $this->runNodeCommand(
            $electronPath,
            $this->quoteExecutable($node).' '.$this->quoteExecutable($installScript),
            $timeout,
        );

        $output = trim($result->output()."\n".$result->errorOutput());

        if (! $result->successful()) {
            return [
                'success' => false,
                'output' => $output."\n\nElectron download failed or timed out. Retry on a stable connection, or set ELECTRON_MIRROR in .env.",
            ];
        }

        return [
            'success' => true,
            'output' => $output,
        ];
    }

    /**
     * @return array{success: bool, output: string}
     */
    protected function ensureElectronDependencies(string $electronPath): array
    {
        if ($this->electronBuilderBinary($electronPath) !== null) {
            return [
                'success' => true,
                'output' => '',
            ];
        }

        $install = $this->runNodeCommand($electronPath, $this->npmCommand().' install', 600);

        $output = trim($install->output()."\n".$install->errorOutput());

        if (! $install->successful()) {
            return [
                'success' => false,
                'output' => $output,
            ];
        }

        if ($this->electronBuilderBinary($electronPath) === null) {
            $rebuild = $this->runNodeCommand($electronPath, $this->npmCommand().' rebuild electron-builder', 300);

            $output = trim($output."\n".$rebuild->output()."\n".$rebuild->errorOutput());
        }

        if ($this->electronBuilderBinary($electronPath) === null) {
            return [
                'success' => false,
                'output' => $output."\n\nelectron-builder binaries are missing. Run `cd electron && npm rebuild electron-builder`.",
            ];
        }

        return [
            'success' => true,
            'output' => $output,
        ];
    }

    protected function electronBuilderBinary(string $electronPath): ?string
    {
        $binary = $electronPath.DIRECTORY_SEPARATOR.'node_modules'
            .DIRECTORY_SEPARATOR.'.bin'
            .DIRECTORY_SEPARATOR.'electron-builder';

        if (PHP_OS_FAMILY === 'Windows') {
            $binary .= '.cmd';
        }

        return is_file($binary) ? $binary : null;
    }
}
