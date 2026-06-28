<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvWriterService
{
    public function __construct(
        protected string $envPath = '',
    ) {
        $this->envPath = $envPath ?: base_path('.env');
    }

    public function exists(): bool
    {
        return File::exists($this->envPath);
    }

    public function read(): string
    {
        if (! $this->exists()) {
            throw new RuntimeException('.env file does not exist.');
        }

        return File::get($this->envPath);
    }

    public function readKey(string $key, ?string $default = null): ?string
    {
        $content = $this->read();
        $pattern = '/^'.preg_quote($key, '/').'=(.*)$/m';

        if (preg_match($pattern, $content, $matches)) {
            return $this->unquote(trim($matches[1]));
        }

        return $default;
    }

    /**
     * @param  array<string, string|null>  $keys
     */
    public function update(array $keys): void
    {
        if (! $this->exists()) {
            $examplePath = base_path('.env.example');

            if (File::exists($examplePath)) {
                File::copy($examplePath, $this->envPath);
            } else {
                File::put($this->envPath, '');
            }
        }

        $content = $this->read();
        $lines = preg_split('/\R/', $content) ?: [];

        foreach ($keys as $key => $value) {
            $formattedValue = $this->formatValue($value);
            $newLine = "{$key}={$formattedValue}";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/';
            $replaced = false;

            foreach ($lines as $index => $line) {
                if (preg_match($pattern, $line)) {
                    $lines[$index] = $newLine;
                    $replaced = true;
                    break;
                }
            }

            if (! $replaced) {
                $lines[] = $newLine;
            }
        }

        File::put($this->envPath, implode(PHP_EOL, $lines).PHP_EOL);
    }

    public function set(string $key, ?string $value): void
    {
        $this->update([$key => $value]);
    }

    protected function formatValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value === '') {
            return '""';
        }

        if (preg_match('/\s|#|=|"/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    protected function unquote(string $value): string
    {
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
