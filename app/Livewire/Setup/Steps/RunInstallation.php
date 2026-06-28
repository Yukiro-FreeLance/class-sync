<?php

namespace App\Livewire\Setup\Steps;

use App\Livewire\Setup\Concerns\ManagesWizardSession;
use App\Models\Role;
use App\Models\User;
use App\Services\Setup\InstallerService;
use App\Services\Setup\SetupPayloadStore;
use App\Services\Users\SuperAdminService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class RunInstallation extends Component
{
    use ManagesWizardSession;

    /** @var array<int, array{step: string, status: string, message: string}> */
    public array $logs = [];

    public int $progress = 0;

    public bool $installing = false;

    public bool $completed = false;

    public bool $failed = false;

    public ?string $errorMessage = null;

    public function startInstallation(InstallerService $installer, SetupPayloadStore $payloadStore): void
    {
        if ($this->installing || $this->completed) {
            return;
        }

        $this->installing = true;
        $this->failed = false;
        $this->errorMessage = null;
        $this->logs = [];
        $this->progress = 0;

        $this->logs = $installer->install();

        $installStepCount = 4;
        $successCount = 0;

        foreach ($this->logs as $log) {
            if ($log['status'] === 'success') {
                $successCount++;
            } else {
                $this->failed = true;
                $this->errorMessage = $log['message'];
                break;
            }
        }

        $this->progress = (int) round(($successCount / $installStepCount) * 80);

        if ($this->failed) {
            $this->installing = false;

            return;
        }

        try {
            $this->finalizeSetup($payloadStore);
            app(InstallerService::class)->markInstallationComplete();
            $payloadStore->clear();
            $this->progress = 100;
            $this->completed = true;
            $this->installing = false;
            $this->dispatch('wizard-next');
        } catch (\Throwable $e) {
            $this->failed = true;
            $this->errorMessage = $e->getMessage();
            $this->installing = false;
            $this->logs[] = [
                'step' => 'finalize',
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function finalizeSetup(SetupPayloadStore $payloadStore): void
    {
        $admin = $this->resolveAdminPayload($payloadStore);
        $application = $this->resolveApplicationPayload($payloadStore);

        $payloadStore->validateAdmin($admin);

        $role = Role::firstOrCreate(
            [
                'name' => app(SuperAdminService::class)->setupRoleName(),
                'guard_name' => 'web',
            ],
            [
                'is_enabled' => true,
            ],
        );

        $attributes = [
            'name' => trim(($admin['first_name'] ?? '').' '.($admin['last_name'] ?? '')),
            'first_name' => $admin['first_name'],
            'last_name' => $admin['last_name'],
            'username' => Str::lower(trim($admin['username'])),
            'email' => Str::lower(trim($admin['email'])),
            'password' => Hash::make($admin['password']),
            'is_active' => true,
            'email_verified_at' => now(),
        ];

        $user = User::query()
            ->where('email', $attributes['email'])
            ->orWhere('username', $attributes['username'])
            ->first();

        if ($user) {
            $user->update($attributes);
        } else {
            $broken = User::query()
                ->where(function ($query) {
                    $query->whereNull('email')
                        ->orWhere('email', '')
                        ->orWhereNull('username')
                        ->orWhere('username', '');
                })
                ->first();

            $user = $broken
                ? tap($broken)->update($attributes)
                : User::query()->create($attributes);
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        app(RolePermissionSeeder::class)->run();
        app(DepartmentSeeder::class)->run();
        app(GradeLevelSeeder::class)->run();
        app(SectionSeeder::class)->run();
        app(SubjectSeeder::class)->run();

        Auth::login($user);

        session(['setup.wizard.admin_user_id' => $user->id]);

        $settingsData = [
            'school_name' => $application['school_name'] ?? '',
            'school_address' => $application['school_address'] ?? '',
            'currency' => $application['currency'] ?? 'USD',
            'semester' => $application['semester'] ?? 'first',
            'logo_path' => $this->persistLogo($application['logo_path'] ?? null),
        ];

        foreach ($settingsData as $key => $value) {
            $payload = [
                'value' => json_encode($value),
                'group' => 'school',
                'updated_at' => now(),
            ];

            if (DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->where('key', $key)->update($payload);
            } else {
                DB::table('settings')->insert(array_merge($payload, [
                    'key' => $key,
                    'created_at' => now(),
                ]));
            }
        }

        $academicYear = $application['academic_year'] ?? date('Y').'-'.(date('Y') + 1);
        $startYear = (int) explode('-', $academicYear)[0];

        if (! DB::table('academic_years')->where('name', $academicYear)->exists()) {
            DB::table('academic_years')->insert([
                'name' => $academicYear,
                'start_date' => "{$startYear}-06-01",
                'end_date' => ($startYear + 1).'-05-31',
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->logs[] = [
            'step' => 'admin_account',
            'status' => 'success',
            'message' => 'Administrator account created.',
        ];

        $this->logs[] = [
            'step' => 'school_settings',
            'status' => 'success',
            'message' => 'School settings and academic year configured.',
        ];
    }

    protected function resolveAdminPayload(SetupPayloadStore $payloadStore): array
    {
        $admin = $payloadStore->admin();

        foreach ($this->wizardData('admin', []) as $key => $value) {
            if ($value !== null && $value !== '') {
                $admin[$key] = $value;
            }
        }

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveApplicationPayload(SetupPayloadStore $payloadStore): array
    {
        $application = $payloadStore->application();

        foreach ($this->wizardData('application', []) as $key => $value) {
            if ($value !== null && $value !== '') {
                $application[$key] = $value;
            }
        }

        return $application;
    }

    protected function persistLogo(?string $tempPath): ?string
    {
        if (! $tempPath || ! Storage::disk('local')->exists($tempPath)) {
            return null;
        }

        $filename = 'logo_'.time().'.'.pathinfo($tempPath, PATHINFO_EXTENSION);
        $destination = 'school/'.$filename;

        File::ensureDirectoryExists(storage_path('app/public/school'));
        Storage::disk('public')->put($destination, Storage::disk('local')->get($tempPath));
        Storage::disk('local')->delete($tempPath);

        return $destination;
    }

    public function retry(InstallerService $installer, SetupPayloadStore $payloadStore): void
    {
        $this->logs = [];
        $this->progress = 0;
        $this->completed = false;
        $this->failed = false;
        $this->startInstallation($installer, $payloadStore);
    }

    public function render()
    {
        return view('livewire.setup.steps.run-installation');
    }
}
