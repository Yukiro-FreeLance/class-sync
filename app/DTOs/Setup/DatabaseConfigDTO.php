<?php

namespace App\DTOs\Setup;

readonly class DatabaseConfigDTO
{
    public function __construct(
        public string $driver,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $database = null,
        public ?string $username = null,
        public ?string $password = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            driver: $data['driver'],
            host: $data['host'] ?? null,
            port: isset($data['port']) ? (int) $data['port'] : null,
            database: $data['database'] ?? null,
            username: $data['username'] ?? null,
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ], fn ($value) => $value !== null);
    }

    public function toEnvKeys(): array
    {
        $connection = match ($this->driver) {
            'mariadb' => 'mariadb',
            'mysql' => 'mysql',
            'sqlite' => 'sqlite',
            default => $this->driver,
        };

        $keys = [
            'DB_CONNECTION' => $connection,
        ];

        if ($connection === 'sqlite') {
            $keys['DB_DATABASE'] = $this->database ?? database_path('database.sqlite');
        } else {
            $keys['DB_HOST'] = $this->host ?? '127.0.0.1';
            $keys['DB_PORT'] = (string) ($this->port ?? ($connection === 'mariadb' ? 3306 : 3306));
            $keys['DB_DATABASE'] = $this->database ?? '';
            $keys['DB_USERNAME'] = $this->username ?? '';
            $keys['DB_PASSWORD'] = $this->password ?? '';
        }

        return $keys;
    }
}
