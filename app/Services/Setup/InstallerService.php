<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class InstallerService
{
    public const LOCK_FILE = 'installed';

    public function __construct(
        protected EnvWriterService $envWriter,
    ) {}

    public function isInstalled(): bool
    {
        if (File::exists(storage_path(self::LOCK_FILE))) {
            return true;
        }

        try {
            if (! $this->envWriter->exists()) {
                return false;
            }

            if (! $this->installationTableExists()) {
                return false;
            }

            return DB::table('installation_status')
                ->where('is_installed', true)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, array{step: string, status: string, message: string}>
     */
    public function install(): array
    {
        $logs = [];

        $steps = [
            'key_generate' => fn () => $this->runKeyGenerate(),
            'migrate' => fn () => $this->runMigrate(),
            'seed' => fn () => $this->runSeed(),
            'storage_link' => fn () => $this->runStorageLink(),
        ];

        foreach ($steps as $step => $callback) {
            try {
                $message = $callback();
                $logs[] = [
                    'step' => $step,
                    'status' => 'success',
                    'message' => $message,
                ];
            } catch (\Throwable $e) {
                $logs[] = [
                    'step' => $step,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];

                break;
            }
        }

        return $logs;
    }

    public function markInstallationComplete(): string
    {
        if (! $this->installationTableExists()) {
            throw new RuntimeException('installation_status table does not exist. Run migrations first.');
        }

        $existing = DB::table('installation_status')->first();

        if ($existing) {
            DB::table('installation_status')->where('id', $existing->id)->update([
                'is_installed' => true,
                'installed_at' => now(),
                'version' => config('app.version', '1.0.0'),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('installation_status')->insert([
                'is_installed' => true,
                'installed_at' => now(),
                'version' => config('app.version', '1.0.0'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        File::put(storage_path(self::LOCK_FILE), now()->toIso8601String());

        $this->envWriter->update([
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);

        $this->runOptimize();

        return 'Installation marked as complete.';
    }

    protected function runKeyGenerate(): string
    {
        if (empty($this->envWriter->readKey('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true]);

            return trim(Artisan::output()) ?: 'Application key generated.';
        }

        return 'Application key already exists.';
    }

    protected function runMigrate(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        return trim(Artisan::output()) ?: 'Database migrations completed.';
    }

    protected function runSeed(): string
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\SetupDatabaseSeeder',
            '--force' => true,
        ]);

        return trim(Artisan::output()) ?: 'Database seeding completed.';
    }

    protected function runStorageLink(): string
    {
        if (File::exists(public_path('storage'))) {
            return 'Storage link already exists.';
        }

        Artisan::call('storage:link');

        return trim(Artisan::output()) ?: 'Storage link created.';
    }

    protected function runOptimize(): string
    {
        Artisan::call('optimize');

        return trim(Artisan::output()) ?: 'Application optimized.';
    }

    protected function installationTableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('installation_status');
        } catch (\Throwable) {
            return false;
        }
    }
}
