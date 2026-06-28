<?php

namespace App\Console\Commands;

use App\Services\Deployment\ApplicationPackageService;
use Illuminate\Console\Command;
use Throwable;

class PackageApplicationCommand extends Command
{
    protected $signature = 'classsync:package-application
                            {--build-assets : Run npm run build before packaging}
                            {--desktop : Build the Windows Electron installer instead of a deploy ZIP}';

    protected $description = 'Create a deployable Class Sync application package';

    public function handle(ApplicationPackageService $packages): int
    {
        if (! $packages->canPackage()) {
            $this->error('Packaging requirements are not met.');

            foreach ($packages->preflightChecks() as $check) {
                $this->line(sprintf('[%s] %s — %s', $check['ok'] ? 'OK' : 'FAIL', $check['label'], $check['detail']));
            }

            return self::FAILURE;
        }

        if ($this->option('build-assets')) {
            $this->info('Building production assets...');
            $assets = $packages->buildAssets();

            if (! $assets['success']) {
                $this->error('Asset build failed.');
                $this->line($assets['output']);

                return self::FAILURE;
            }

            $this->line($assets['output']);
        }

        try {
            if ($this->option('desktop')) {
                $this->info('Building Windows desktop installer...');
                $result = $packages->buildDesktopInstaller();

                if (! $result['success']) {
                    $this->error('Desktop installer build failed.');
                    $this->line($result['output']);

                    return self::FAILURE;
                }

                $package = $result['package'];
                $this->line($result['output']);
            } else {
                $this->info('Creating deployment package...');
                $package = $packages->createDeployPackage();
            }

            $this->info("Package created: {$package['filename']}");
            $this->line("Path: {$package['path']}");
            $this->line('Size: '.$this->formatBytes($package['size']));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
