<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    public function create(): array
    {
        $this->ensureBackupDirectory();

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        return match ($driver) {
            'sqlite' => $this->backupSqlite(),
            'mysql', 'mariadb' => $this->backupMysql($connection),
            default => throw new RuntimeException("Backup not supported for driver: {$driver}"),
        };
    }

    public function restore(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);

        if (! File::exists($path)) {
            throw new RuntimeException("Backup file not found: {$filename}");
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        match ($driver) {
            'sqlite' => $this->restoreSqlite($path),
            'mysql', 'mariadb' => $this->restoreMysql($path, $connection),
            default => throw new RuntimeException("Restore not supported for driver: {$driver}"),
        };
    }

    /**
     * @return array<int, array{filename: string, path: string, size: int, created_at: string}>
     */
    public function list(): array
    {
        $this->ensureBackupDirectory();

        $files = File::files($this->backupPath);

        return collect($files)
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'created_at' => date('c', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function delete(string $filename): bool
    {
        $path = $this->resolveBackupPath($filename);

        if (! File::exists($path)) {
            return false;
        }

        return File::delete($path);
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    protected function backupSqlite(): array
    {
        $database = config('database.connections.sqlite.database');

        if (! File::exists($database)) {
            throw new RuntimeException('SQLite database file does not exist.');
        }

        $filename = 'backup_sqlite_'.now()->format('Y-m-d_His').'.sqlite';
        $destination = $this->backupPath.DIRECTORY_SEPARATOR.$filename;

        File::copy($database, $destination);

        return $this->formatBackupInfo($filename, $destination);
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    protected function backupMysql(string $connection): array
    {
        $config = config("database.connections.{$connection}");
        $filename = "backup_{$connection}_".now()->format('Y-m-d_His').'.sql';
        $destination = $this->backupPath.DIRECTORY_SEPARATOR.$filename;

        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['database']),
        );

        $result = Process::run("{$command} > ".escapeshellarg($destination));

        if (! $result->successful()) {
            File::delete($destination);
            throw new RuntimeException('MySQL backup failed: '.$result->errorOutput());
        }

        return $this->formatBackupInfo($filename, $destination);
    }

    protected function restoreSqlite(string $backupPath): void
    {
        $database = config('database.connections.sqlite.database');

        DB::disconnect();

        File::copy($backupPath, $database);
    }

    protected function restoreMysql(string $backupPath, string $connection): void
    {
        $config = config("database.connections.{$connection}");

        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s < %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath),
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new RuntimeException('MySQL restore failed: '.$result->errorOutput());
        }
    }

    protected function ensureBackupDirectory(): void
    {
        if (! File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    protected function resolveBackupPath(string $filename): string
    {
        $filename = basename($filename);

        return $this->backupPath.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    protected function formatBackupInfo(string $filename, string $path): array
    {
        return [
            'filename' => $filename,
            'path' => $path,
            'size' => File::size($path),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
