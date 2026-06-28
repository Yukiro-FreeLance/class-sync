<?php

namespace App\Livewire\Setup\Steps;

use App\DTOs\Setup\ApplicationConfigDTO;
use App\Enums\Semester;
use App\Livewire\Setup\Concerns\ManagesWizardSession;
use App\Services\Setup\EnvWriterService;
use App\Services\Setup\SetupPayloadStore;
use Livewire\Component;
use Livewire\WithFileUploads;

class ApplicationConfig extends Component
{
    use ManagesWizardSession;
    use WithFileUploads;

    public string $app_name = 'Class Sync';

    public string $timezone = 'UTC';

    public string $locale = 'en';

    public string $currency = 'USD';

    public string $school_name = '';

    public string $school_address = '';

    public string $academic_year = '';

    public string $semester = 'first';

    public $logo = null;

    public ?string $logo_path = null;

    public function mount(): void
    {
        $saved = $this->wizardData('application', []);

        $this->app_name = $saved['app_name'] ?? 'Class Sync';
        $this->timezone = $saved['timezone'] ?? config('app.timezone', 'UTC');
        $this->locale = $saved['locale'] ?? config('app.locale', 'en');
        $this->currency = $saved['currency'] ?? 'USD';
        $this->school_name = $saved['school_name'] ?? '';
        $this->school_address = $saved['school_address'] ?? '';
        $this->academic_year = $saved['academic_year'] ?? $this->defaultAcademicYear();
        $this->semester = $saved['semester'] ?? Semester::First->value;
        $this->logo_path = $saved['logo_path'] ?? null;
    }

    protected function defaultAcademicYear(): string
    {
        $year = (int) date('Y');

        return "{$year}-".($year + 1);
    }

    protected function rules(): array
    {
        return [
            'app_name' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
            'locale' => 'required|string|max:10',
            'currency' => 'required|string|max:10',
            'school_name' => 'required|string|max:255',
            'school_address' => 'required|string|max:500',
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|in:'.implode(',', array_keys(Semester::options())),
            'logo' => 'nullable|image|max:2048',
        ];
    }

    public function save(EnvWriterService $envWriter, SetupPayloadStore $payloadStore): void
    {
        $this->validate();

        if ($this->logo) {
            $this->logo_path = $this->logo->store('setup/logos', 'local');
        }

        $dto = new ApplicationConfigDTO(
            appName: $this->app_name,
            appUrl: url('/'),
            timezone: $this->timezone,
            locale: $this->locale,
        );

        $envWriter->update($dto->toEnvKeys());

        $application = [
            'app_name' => $this->app_name,
            'app_url' => url('/'),
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'school_name' => $this->school_name,
            'school_address' => $this->school_address,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'logo_path' => $this->logo_path,
        ];

        $this->setWizardData('application', $application);
        $payloadStore->saveApplication($application);

        $this->dispatch('wizard-next');
    }

    /**
     * @return array<string, string>
     */
    public function getSemesterOptionsProperty(): array
    {
        return Semester::options();
    }

    /**
     * @return array<int, string>
     */
    public function getTimezoneOptionsProperty(): array
    {
        return collect(timezone_identifiers_list())
            ->filter(fn (string $tz) => str_contains($tz, '/'))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getLocaleOptionsProperty(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'pt' => 'Portuguese',
            'fil' => 'Filipino',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getCurrencyOptionsProperty(): array
    {
        return [
            'USD' => 'USD — US Dollar',
            'EUR' => 'EUR — Euro',
            'GBP' => 'GBP — British Pound',
            'PHP' => 'PHP — Philippine Peso',
            'AUD' => 'AUD — Australian Dollar',
            'CAD' => 'CAD — Canadian Dollar',
            'JPY' => 'JPY — Japanese Yen',
        ];
    }

    public function render()
    {
        return view('livewire.setup.steps.application-config');
    }
}
