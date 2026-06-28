<?php

namespace App\Livewire\Settings;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogService;
use App\Services\Deployment\ApplicationPackageService;
use App\Services\Users\SuperAdminService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Application Package')]
class ApplicationPackage extends Component
{
    public bool $isBuildingAssets = false;

    public bool $isPackaging = false;

    public bool $isBuildingDesktop = false;

    public ?string $lastOutput = null;

    public function mount(SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }
    }

    public function buildAssets(ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $this->isBuildingAssets = true;
        $this->lastOutput = null;

        try {
            set_time_limit(0);

            $result = $packages->buildAssets();
            $this->lastOutput = $result['output'];

            if ($result['success']) {
                $audit->log(AuditAction::Export, description: 'Built production frontend assets for application packaging.');
                $this->dispatch('toast', message: 'Production assets built successfully.', type: 'success');
            } else {
                $this->dispatch('toast', message: 'Asset build failed. Review the build log below.', type: 'error');
            }
        } catch (Throwable $exception) {
            $this->lastOutput = $exception->getMessage();
            $this->dispatch('toast', message: 'Asset build failed.', type: 'error');
        } finally {
            $this->isBuildingAssets = false;
        }
    }

    public function createPackage(ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $this->isPackaging = true;
        $this->lastOutput = null;

        try {
            set_time_limit(0);

            $package = $packages->createDeployPackage();

            $audit->log(
                AuditAction::Export,
                description: 'Created application deployment package.',
                properties: ['filename' => $package['filename'], 'size' => $package['size']],
            );

            $this->dispatch('toast', message: 'Application package created successfully.', type: 'success');
        } catch (Throwable $exception) {
            $this->lastOutput = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        } finally {
            $this->isPackaging = false;
        }
    }

    public function buildDesktopInstaller(ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $this->isBuildingDesktop = true;
        $this->lastOutput = null;

        try {
            set_time_limit(0);

            $result = $packages->buildDesktopInstaller();
            $this->lastOutput = $result['output'];

            if (! $result['success']) {
                $this->dispatch('toast', message: 'Desktop installer build failed. Review the build log below.', type: 'error');

                return;
            }

            $audit->log(
                AuditAction::Export,
                description: 'Built Windows desktop installer.',
                properties: ['filename' => $result['package']['filename'] ?? null],
            );

            $this->dispatch('toast', message: 'Windows installer built successfully.', type: 'success');
        } catch (Throwable $exception) {
            $this->lastOutput = $exception->getMessage();
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        } finally {
            $this->isBuildingDesktop = false;
        }
    }

    public function deletePackage(string $filename, ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        if (! $packages->delete($filename)) {
            $this->dispatch('toast', message: 'Package not found.', type: 'warning');

            return;
        }

        $audit->log(
            AuditAction::Delete,
            description: 'Deleted application package.',
            properties: ['filename' => $filename],
        );

        $this->dispatch('toast', message: 'Package deleted.', type: 'success');
    }

    protected function viewData(ApplicationPackageService $packages): array
    {
        return [
            'checks' => $packages->preflightChecks(),
            'canPackage' => $packages->canPackage(),
            'packages' => $packages->list(),
            'appVersion' => config('classsync.version', '1.0.0'),
        ];
    }

    public function render(ApplicationPackageService $packages)
    {
        return view('livewire.settings.application-package', $this->viewData($packages));
    }
}
