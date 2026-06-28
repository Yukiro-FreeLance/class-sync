<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandingService
{
    public const DEFAULT_SIDEBAR_COLOR = '#ffffff';

    public const DEFAULT_HEADER_COLOR = '#ffffff';

    public const DEFAULT_BACKGROUND_COLOR = '#f4f6fb';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function logoPath(): ?string
    {
        $path = $this->settings->get('logo_path', null, 'general')
            ?? $this->settings->get('logo_path', null, 'school');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return $path;
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function schoolName(): string
    {
        $name = $this->settings->get('school_name', null, 'general');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return config('app.name', 'Class Sync');
    }

    public function appSubtitle(): string
    {
        $subtitle = $this->settings->get('app_subtitle', null, 'general');

        if (is_string($subtitle) && $subtitle !== '') {
            return $subtitle;
        }

        return 'Class Sync';
    }

    public function sidebarAcronym(): string
    {
        $code = $this->settings->get('school_code', null, 'general');

        if (is_string($code) && $code !== '') {
            return Str::upper(Str::limit(trim($code), 8, ''));
        }

        $acronym = $this->settings->get('sidebar_acronym', null, 'general');

        if (is_string($acronym) && $acronym !== '') {
            return Str::upper(Str::limit($acronym, 8, ''));
        }

        return $this->initialsFromName($this->schoolName());
    }

    public function schoolCode(): ?string
    {
        $code = $this->settings->get('school_code', null, 'general');

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        return Str::upper(trim($code));
    }

    public function sidebarColor(): string
    {
        return $this->colorSetting('sidebar_color', self::DEFAULT_SIDEBAR_COLOR);
    }

    public function headerColor(): string
    {
        return $this->colorSetting('header_color', self::DEFAULT_HEADER_COLOR);
    }

    public function backgroundColor(): string
    {
        return $this->colorSetting('background_color', self::DEFAULT_BACKGROUND_COLOR);
    }

    /**
     * @return array{sidebar_color: string, header_color: string, background_color: string}
     */
    public function layoutColors(): array
    {
        return [
            'sidebar_color' => $this->sidebarColor(),
            'header_color' => $this->headerColor(),
            'background_color' => $this->backgroundColor(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function layoutCssVariables(): array
    {
        $header = $this->headerColor();

        return [
            '--app-sidebar-bg' => $this->sidebarColor(),
            '--app-header-bg' => $this->hexToRgba($header, 0.92),
            '--app-main-bg' => $this->backgroundColor(),
            '--app-sidebar-brand-bg' => $this->hexToRgba($header, 0.35),
        ];
    }

    public function layoutCssVariableString(): string
    {
        return collect($this->layoutCssVariables())
            ->map(fn (string $value, string $key) => "{$key}: {$value}")
            ->implode('; ');
    }

    public static function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', trim($color));
    }

    public static function normalizeHexColor(string $color): string
    {
        $color = strtolower(trim($color));

        if (! self::isValidHexColor($color)) {
            return $color;
        }

        if (strlen($color) === 4) {
            return '#'.implode('', array_map(
                fn (string $char) => $char.$char,
                str_split(substr($color, 1))
            ));
        }

        return $color;
    }

    /**
     * @return array{logo_url: ?string, sidebar_acronym: string, school_name: string, school_code: ?string, app_subtitle: string, sidebar_color: string, header_color: string, background_color: string}
     */
    public function forLayout(): array
    {
        return [
            'logo_url' => $this->logoUrl(),
            'sidebar_acronym' => $this->sidebarAcronym(),
            'school_name' => $this->schoolName(),
            'school_code' => $this->schoolCode(),
            'app_subtitle' => $this->appSubtitle(),
            ...$this->layoutColors(),
        ];
    }

    public function deleteLogoFile(): void
    {
        $path = $this->logoPath();

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function colorSetting(string $key, string $default): string
    {
        $value = $this->settings->get($key, null, 'general');

        if (! is_string($value) || ! self::isValidHexColor($value)) {
            return $default;
        }

        return self::normalizeHexColor($value);
    }

    protected function hexToRgba(string $hex, float $alpha): string
    {
        $hex = self::normalizeHexColor($hex);
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return "rgba(255, 255, 255, {$alpha})";
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return "rgba({$red}, {$green}, {$blue}, {$alpha})";
    }

    protected function initialsFromName(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];

        if ($words === []) {
            return 'CS';
        }

        if (count($words) === 1) {
            return Str::upper(Str::substr($words[0], 0, 4));
        }

        $initials = collect($words)
            ->take(4)
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');

        return Str::upper($initials);
    }
}
