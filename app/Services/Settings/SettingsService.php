<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_PREFIX = 'settings';

    protected const CACHE_TTL = 3600;

    public function __construct(
        protected Setting $model,
    ) {}

    public function get(string $key, mixed $default = null, ?string $group = 'general'): mixed
    {
        return Cache::remember(
            $this->cacheKey($group, $key),
            self::CACHE_TTL,
            fn () => $this->getFromDatabase($key, $default, $group)
        );
    }

    public function set(string $key, mixed $value, ?string $group = 'general'): Setting
    {
        $setting = $this->model->newQuery()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => gettype($value)]
        );

        Cache::forget($this->cacheKey($group, $key));
        Cache::forget($this->groupCacheKey($group));

        return $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        return Cache::remember(
            $this->groupCacheKey($group),
            self::CACHE_TTL,
            fn () => $this->model->newQuery()
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function forget(string $key, ?string $group = 'general'): bool
    {
        $deleted = (bool) $this->model->newQuery()
            ->where('group', $group)
            ->where('key', $key)
            ->delete();

        Cache::forget($this->cacheKey($group, $key));
        Cache::forget($this->groupCacheKey($group));

        return $deleted;
    }

    public function flushCache(?string $group = null): void
    {
        if ($group) {
            $keys = $this->model->newQuery()
                ->where('group', $group)
                ->pluck('key');

            foreach ($keys as $key) {
                Cache::forget($this->cacheKey($group, $key));
            }

            Cache::forget($this->groupCacheKey($group));

            return;
        }

        $settings = $this->model->newQuery()->get(['group', 'key']);

        foreach ($settings as $setting) {
            Cache::forget($this->cacheKey($setting->group, $setting->key));
            Cache::forget($this->groupCacheKey($setting->group));
        }
    }

    protected function getFromDatabase(string $key, mixed $default, ?string $group): mixed
    {
        $setting = $this->model->newQuery()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    protected function cacheKey(?string $group, string $key): string
    {
        return self::CACHE_PREFIX.".{$group}.{$key}";
    }

    protected function groupCacheKey(string $group): string
    {
        return self::CACHE_PREFIX.".group.{$group}";
    }
}
