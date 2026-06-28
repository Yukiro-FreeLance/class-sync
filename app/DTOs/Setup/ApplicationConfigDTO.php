<?php

namespace App\DTOs\Setup;

readonly class ApplicationConfigDTO
{
    public function __construct(
        public string $appName,
        public string $appUrl,
        public string $appEnv = 'production',
        public bool $appDebug = false,
        public string $timezone = 'UTC',
        public string $locale = 'en',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            appName: $data['app_name'],
            appUrl: rtrim($data['app_url'], '/'),
            appEnv: $data['app_env'] ?? 'production',
            appDebug: (bool) ($data['app_debug'] ?? false),
            timezone: $data['timezone'] ?? 'UTC',
            locale: $data['locale'] ?? 'en',
        );
    }

    public function toEnvKeys(): array
    {
        return [
            'APP_NAME' => $this->appName,
            'APP_URL' => $this->appUrl,
            'APP_ENV' => $this->appEnv,
            'APP_DEBUG' => $this->appDebug ? 'true' : 'false',
            'APP_TIMEZONE' => $this->timezone,
            'APP_LOCALE' => $this->locale,
        ];
    }
}
