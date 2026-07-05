<?php

namespace App\Livewire\Settings;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogService;
use App\Services\Deployment\ApplicationPackageService;
use App\Services\Settings\BrandingService;
use App\Services\Users\SuperAdminService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
#[Title('Application Package')]
class ApplicationPackage extends Component
{
    use WithFileUploads;

    public bool $isBuildingAssets = false;

    public bool $isPackaging = false;

    public bool $isBuildingDesktop = false;

    public ?string $lastOutput = null;

    public $desktopIcon = null;

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
            $this->dispatch('toast', message: 'Package not found in download storage.', type: 'warning');

            return;
        }

        $audit->log(
            AuditAction::Delete,
            description: 'Deleted application package.',
            properties: ['filename' => $filename],
        );

        $this->dispatch('toast', message: 'Package deleted.', type: 'success');
    }

    public function importDesktopInstaller(string $filename, ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        try {
            $package = $packages->importDesktopInstaller($filename);

            $audit->log(
                AuditAction::Export,
                description: 'Imported desktop installer into download storage.',
                properties: ['filename' => $package['filename']],
            );

            $this->dispatch('toast', message: 'Installer saved for download.', type: 'success');
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
    }

    public function uploadDesktopIcon(ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $this->validate([
            'desktopIcon' => ['required', 'file', 'mimes:png,jpg,jpeg,ico', 'max:2048'],
        ]);

        try {
            $icon = $packages->storeDesktopIcon($this->desktopIcon);

            $audit->log(
                AuditAction::Update,
                description: 'Uploaded desktop application icon.',
                properties: ['filename' => $icon['filename']],
            );

            $this->desktopIcon = null;
            $this->dispatch('toast', message: 'Desktop icon uploaded successfully.', type: 'success');
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
    }

    public function useSchoolLogoAsDesktopIcon(
        ApplicationPackageService $packages,
        BrandingService $branding,
        AuditLogService $audit,
        SuperAdminService $superAdmin,
    ): void {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $logoPath = $branding->logoPath();

        if (! is_string($logoPath) || ! Storage::disk('public')->exists($logoPath)) {
            $this->dispatch('toast', message: 'Upload a school logo in General Settings first.', type: 'warning');

            return;
        }

        try {
            $icon = $packages->copySchoolLogoAsDesktopIcon(Storage::disk('public')->path($logoPath));

            $audit->log(
                AuditAction::Update,
                description: 'Copied school logo to desktop application icon.',
                properties: ['filename' => $icon['filename']],
            );

            $this->dispatch('toast', message: 'School logo applied as desktop icon.', type: 'success');
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
    }

    public function removeDesktopIcon(ApplicationPackageService $packages, AuditLogService $audit, SuperAdminService $superAdmin): void
    {
        if (! $superAdmin->is(auth()->user())) {
            abort(403);
        }

        $packages->removeDesktopIcon();

        $audit->log(AuditAction::Delete, description: 'Removed desktop application icon.');

        $this->desktopIcon = null;
        $this->dispatch('toast', message: 'Desktop icon removed.', type: 'success');
    }

    protected function viewData(ApplicationPackageService $packages): array
    {
        return [
            'checks' => $packages->preflightChecks(),
            'canPackage' => $packages->canPackage(),
            'canBuildDesktop' => $packages->canBuildDesktop(),
            'packages' => $packages->list(),
            'latestDesktopInstaller' => $packages->latestDesktopInstaller(),
            'desktopIconInfo' => $packages->desktopIconInfo(),
            'appVersion' => config('classsync.version', '1.0.0'),
        ];
    }

    public function render(ApplicationPackageService $packages)
    {
        return view('livewire.settings.application-package', $this->viewData($packages));
    }
}
