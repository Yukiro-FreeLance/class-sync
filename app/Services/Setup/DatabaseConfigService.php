<?php

namespace App\Services\Setup;

use App\DTOs\Setup\DatabaseConfigDTO;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;

class DatabaseConfigService
{
    public function __construct(
        protected EnvWriterService $envWriter,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(DatabaseConfigDTO $config): array
    {
        $connectionName = 'setup_test_'.uniqid();

        try {
            if (in_array($config->driver, ['mysql', 'mariadb'], true)) {
                $this->ensureDatabaseExists($config);
            }

            $this->registerTemporaryConnection($connectionName, $config);
            DB::connection($connectionName)->getPdo();
            DB::connection($connectionName)->select('SELECT 1');

            $message = 'Database connection successful.';

            if (in_array($config->driver, ['mysql', 'mariadb'], true)) {
                $message = "Database connection successful. Database \"{$config->database}\" is ready.";
            }

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        } finally {
            DB::purge($connectionName);
            Config::offsetUnset("database.connections.{$connectionName}");
        }
    }

    public function writeConfig(DatabaseConfigDTO $config): void
    {
        if ($config->driver === 'sqlite' && $config->database) {
            $databasePath = $config->database;

            if (! str_starts_with($databasePath, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $databasePath)) {
                $databasePath = database_path($databasePath);
            }

            $directory = dirname($databasePath);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            if (! file_exists($databasePath)) {
                touch($databasePath);
            }

            $config = new DatabaseConfigDTO(
                driver: $config->driver,
                database: $databasePath,
            );
        } elseif (in_array($config->driver, ['mysql', 'mariadb'], true)) {
            $this->ensureDatabaseExists($config);
        }

        $this->envWriter->update($config->toEnvKeys());
    }

    public function ensureDatabaseExists(DatabaseConfigDTO $config): void
    {
        if (! in_array($config->driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = $config->database;

        if (! $database) {
            throw new RuntimeException('Database name is required.');
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            throw new RuntimeException('Database name may only contain letters, numbers, and underscores.');
        }

        $host = $config->host ?? '127.0.0.1';
        $port = $config->port ?? 3306;
        $username = $config->username ?? '';
        $password = $config->password ?? '';

        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                str_replace('`', '``', $database),
            ));
        } catch (PDOException $e) {
            throw new RuntimeException('Could not create database: '.$e->getMessage(), 0, $e);
        }
    }

    protected function registerTemporaryConnection(string $name, DatabaseConfigDTO $config): void
    {
        $driver = match ($config->driver) {
            'mariadb' => 'mysql',
            default => $config->driver,
        };

        $settings = [
            'driver' => $driver,
            'prefix' => '',
        ];

        if ($driver === 'sqlite') {
            $database = $config->database ?? database_path('database.sqlite');

            if (! str_starts_with($database, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $database)) {
                $database = database_path($database);
            }

            $settings['database'] = $database;
        } else {
            $settings['host'] = $config->host ?? '127.0.0.1';
            $settings['port'] = $config->port ?? 3306;
            $settings['database'] = $config->database ?? '';
            $settings['username'] = $config->username ?? '';
            $settings['password'] = $config->password ?? '';
            $settings['charset'] = 'utf8mb4';
            $settings['collation'] = 'utf8mb4_unicode_ci';
        }

        Config::set("database.connections.{$name}", $settings);
    }
}
