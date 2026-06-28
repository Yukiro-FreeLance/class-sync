<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SetupPayloadStore
{
    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: storage_path('app/setup/payload.json');
    }

    /**
     * @param  array<string, mixed>  $admin
     */
    public function saveAdmin(array $admin): void
    {
        $payload = $this->read();

        $password = $admin['password'] ?? '';
        unset($admin['password']);

        $payload['admin'] = array_merge($admin, [
            'password' => $password !== '' ? Crypt::encryptString($password) : '',
        ]);

        $this->write($payload);
    }

    /**
     * @param  array<string, mixed>  $application
     */
    public function saveApplication(array $application): void
    {
        $payload = $this->read();
        $payload['application'] = $application;
        $this->write($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function admin(): array
    {
        $admin = $this->read()['admin'] ?? [];

        if (! empty($admin['password'])) {
            try {
                $admin['password'] = Crypt::decryptString($admin['password']);
            } catch (\Throwable) {
                $admin['password'] = '';
            }
        }

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function application(): array
    {
        return $this->read()['application'] ?? [];
    }

    public function clear(): void
    {
        if (File::exists($this->path)) {
            File::delete($this->path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function read(): array
    {
        if (! File::exists($this->path)) {
            return [];
        }

        $decoded = json_decode(File::get($this->path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function write(array $payload): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, mixed>  $admin
     */
    public function validateAdmin(array $admin): void
    {
        foreach (['first_name', 'last_name', 'username', 'email', 'password'] as $field) {
            if (blank($admin[$field] ?? null)) {
                throw new RuntimeException("Administrator {$field} is missing. Go back to the Admin Account step and try again.");
            }
        }

        if (! filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Administrator email is invalid.');
        }
    }
}
