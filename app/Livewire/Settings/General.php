<?php

namespace App\Livewire\Settings;

use App\Enums\Theme;
use App\Models\Setting;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Settings')]
class General extends Component
{
    use WithFileUploads;

    public string $school_name = '';

    public string $school_code = '';

    public string $school_address = '';

    public string $school_phone = '';

    public string $school_email = '';

    public string $app_subtitle = 'Class Sync';

    public ?string $current_logo_url = null;

    public $logo = null;

    public string $late_threshold = '08:00';

    public bool $auto_checkout = false;

    public string $checkout_time = '17:00';

    public string $theme = 'system';

    public string $sidebar_color = BrandingService::DEFAULT_SIDEBAR_COLOR;

    public string $header_color = BrandingService::DEFAULT_HEADER_COLOR;

    public string $background_color = BrandingService::DEFAULT_BACKGROUND_COLOR;

    public function mount(SettingsService $settings, BrandingService $branding): void
    {
        Gate::authorize('update', Setting::class);

        $general = $settings->getGroup('general');
        $attendance = $settings->getGroup('attendance');

        $this->school_name = $general['school_name'] ?? '';
        $this->school_code = $general['school_code'] ?? $general['sidebar_acronym'] ?? '';
        $this->school_address = $general['school_address'] ?? '';
        $this->school_phone = $general['school_phone'] ?? '';
        $this->school_email = $general['school_email'] ?? '';
        $this->app_subtitle = $general['app_subtitle'] ?? $branding->appSubtitle();
        $this->current_logo_url = $branding->logoUrl();
        $this->late_threshold = $attendance['late_threshold'] ?? '08:00';
        $this->auto_checkout = (bool) ($attendance['auto_checkout'] ?? false);
        $this->checkout_time = $attendance['checkout_time'] ?? '17:00';
        $this->theme = $general['theme'] ?? Theme::System->value;
        $this->sidebar_color = $branding->sidebarColor();
        $this->header_color = $branding->headerColor();
        $this->background_color = $branding->backgroundColor();
    }

    public function save(SettingsService $settings, BrandingService $branding): void
    {
        Gate::authorize('update', Setting::class);

        $this->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_code' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-]+$/'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:30'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'app_subtitle' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'late_threshold' => ['required', 'date_format:H:i'],
            'auto_checkout' => ['boolean'],
            'checkout_time' => ['required', 'date_format:H:i'],
            'theme' => ['required', 'in:'.implode(',', array_keys(Theme::options()))],
            'sidebar_color' => ['required', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'header_color' => ['required', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
        ]);

        $schoolCode = strtoupper(trim($this->school_code));

        $generalSettings = [
            'school_name' => $this->school_name,
            'school_code' => $schoolCode,
            'school_address' => $this->school_address,
            'school_phone' => $this->school_phone,
            'school_email' => $this->school_email,
            'sidebar_acronym' => $schoolCode,
            'app_subtitle' => $this->app_subtitle,
            'theme' => $this->theme,
            'sidebar_color' => BrandingService::normalizeHexColor($this->sidebar_color),
            'header_color' => BrandingService::normalizeHexColor($this->header_color),
            'background_color' => BrandingService::normalizeHexColor($this->background_color),
        ];

        if ($this->logo) {
            $branding->deleteLogoFile();
            $generalSettings['logo_path'] = $this->logo->store('school', 'public');
            $this->logo = null;
        }

        $settings->setMany($generalSettings, 'general');

        $settings->setMany([
            'late_threshold' => $this->late_threshold,
            'auto_checkout' => $this->auto_checkout,
            'checkout_time' => $this->checkout_time,
        ], 'attendance');

        $this->current_logo_url = $branding->logoUrl();

        $this->dispatch('layout-colors-updated', variables: $branding->layoutCssVariables());
        $this->dispatch('toast', message: 'Settings saved successfully.', type: 'success');
    }

    public function removeLogo(SettingsService $settings, BrandingService $branding): void
    {
        Gate::authorize('update', Setting::class);

        $branding->deleteLogoFile();
        $settings->forget('logo_path', 'general');
        $settings->forget('logo_path', 'school');

        $this->current_logo_url = null;
        $this->logo = null;

        $this->dispatch('toast', message: 'Logo removed.', type: 'success');
    }

    public function resetLayoutColors(): void
    {
        $this->sidebar_color = BrandingService::DEFAULT_SIDEBAR_COLOR;
        $this->header_color = BrandingService::DEFAULT_HEADER_COLOR;
        $this->background_color = BrandingService::DEFAULT_BACKGROUND_COLOR;
    }

    protected function viewData(): array
    {
        return [
            'themes' => Theme::options(),
        ];
    }

    public function render()
    {
        return view('livewire.settings.general', $this->viewData());
    }
}
