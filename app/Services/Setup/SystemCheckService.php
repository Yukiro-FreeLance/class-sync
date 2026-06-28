<?php

namespace App\Services\Setup;

class SystemCheckService
{
    /**
     * @return array<string, array{status: string, message: string}>
     */
    public function run(): array
    {
        return [
            'php_version' => $this->checkPhpVersion(),
            'extensions' => $this->checkExtensions(),
            'storage_writable' => $this->checkWritable(storage_path()),
            'bootstrap_cache_writable' => $this->checkWritable(base_path('bootstrap/cache')),
            'database_drivers' => $this->checkDatabaseDrivers(),
        ];
    }

    public function passes(): bool
    {
        foreach ($this->run() as $check) {
            if (($check['status'] ?? '') === 'fail') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkPhpVersion(): array
    {
        $required = '8.3.0';
        $current = PHP_VERSION;

        if (version_compare($current, $required, '>=')) {
            return [
                'status' => 'pass',
                'message' => "PHP {$current} meets the minimum requirement ({$required}+).",
            ];
        }

        return [
            'status' => 'fail',
            'message' => "PHP {$current} is installed. PHP {$required} or higher is required.",
        ];
    }

    /**
     * @return array{status: string, message: string, details?: array<string, array{status: string, message: string}>}
     */
    protected function checkExtensions(): array
    {
        $required = ['pdo', 'openssl', 'mbstring', 'fileinfo', 'zip', 'bcmath'];
        $details = [];
        $hasFailure = false;
        $hasWarning = false;

        foreach ($required as $extension) {
            $loaded = extension_loaded($extension);

            $details[$extension] = [
                'status' => $loaded ? 'pass' : 'fail',
                'message' => $loaded
                  ? "Extension {$extension} is loaded."
                  : "Extension {$extension} is not loaded.",
            ];

            if (! $loaded) {
                $hasFailure = true;
            }
        }

        if ($hasFailure) {
            return [
                'status' => 'fail',
                'message' => 'One or more required PHP extensions are missing.',
                'details' => $details,
            ];
        }

        if ($hasWarning) {
            return [
                'status' => 'warning',
                'message' => 'All required extensions are loaded with warnings.',
                'details' => $details,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'All required PHP extensions are loaded.',
            'details' => $details,
        ];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkWritable(string $path): array
    {
        if (! is_dir($path)) {
            return [
                'status' => 'fail',
                'message' => "Directory does not exist: {$path}",
            ];
        }

        if (! is_writable($path)) {
            return [
                'status' => 'fail',
                'message' => "Directory is not writable: {$path}",
            ];
        }

        $testFile = $path.DIRECTORY_SEPARATOR.'.write_test_'.uniqid();

        try {
            if (file_put_contents($testFile, 'test') === false) {
                return [
                    'status' => 'fail',
                    'message' => "Unable to write to directory: {$path}",
                ];
            }

            unlink($testFile);

            return [
                'status' => 'pass',
                'message' => "Directory is writable: {$path}",
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'fail',
                'message' => "Write test failed for {$path}: {$e->getMessage()}",
            ];
        }
    }

    /**
     * @return array{status: string, message: string, details?: array<string, array{status: string, message: string}>}
     */
    protected function checkDatabaseDrivers(): array
    {
        $drivers = [
            'pdo_sqlite' => 'SQLite',
            'pdo_mysql' => 'MySQL',
        ];
        $details = [];
        $available = 0;

        foreach ($drivers as $extension => $label) {
            $loaded = extension_loaded($extension);

            $details[$extension] = [
                'status' => $loaded ? 'pass' : 'warning',
                'message' => $loaded
                  ? "{$label} driver ({$extension}) is available."
                  : "{$label} driver ({$extension}) is not available.",
            ];

            if ($loaded) {
                $available++;
            }
        }

        if ($available === 0) {
            return [
                'status' => 'fail',
                'message' => 'No database drivers are available.',
                'details' => $details,
            ];
        }

        if ($available < count($drivers)) {
            return [
                'status' => 'warning',
                'message' => 'Some database drivers are unavailable.',
                'details' => $details,
            ];
        }

        return [
            'status' => 'pass',
            'message' => 'All database drivers are available.',
            'details' => $details,
        ];
    }
}
