<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Backup;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Policies\ApplicationPackagePolicy;
use App\Policies\AttendancePolicy;
use App\Policies\BackupPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use App\Services\Settings\BrandingService;
use App\Services\Setup\InstallerService;
use App\Services\Users\SuperAdminService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SuperAdminService::class);

        if ($this->shouldUseFileDrivers()) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $superAdmin = app(SuperAdminService::class);

        Gate::before(function (User $user, string $ability, ...$arguments) use ($superAdmin) {
            if ($superAdmin->bypassesAllAuthorization($user)) {
                return true;
            }

            if ($ability === $superAdmin->manageRolesAbility()) {
                return null;
            }

            $model = $arguments[0] ?? null;

            if ($model instanceof User) {
                return null;
            }

            if (! $user->hasRole(UserRole::Administrator->value)) {
                return null;
            }

            if ($model instanceof Student && in_array($ability, ['archive', 'restore', 'update', 'enroll', 'delete'], true)) {
                return null;
            }

            return true;
        });

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(AttendanceRecord::class, AttendancePolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Backup::class, BackupPolicy::class);
        Gate::policy('application-package', ApplicationPackagePolicy::class);
        Gate::policy('report', ReportPolicy::class);

        View::composer('layouts.app', function ($view) {
            $view->with('branding', app(BrandingService::class)->forLayout());
        });
    }

    protected function shouldUseFileDrivers(): bool
    {
        return ! file_exists(storage_path(InstallerService::LOCK_FILE));
    }
}
