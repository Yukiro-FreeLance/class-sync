<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Deployment\ApplicationPackageService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ApplicationPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_view_application_package_page(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('settings.application-package'))
            ->assertOk()
            ->assertSee('Application Package');
    }

    public function test_administrator_cannot_view_application_package_page(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $this->actingAs($admin)
            ->get(route('settings.application-package'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_deploy_package(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $package = app(ApplicationPackageService::class)->createDeployPackage();

        $this->assertTrue(File::exists($package['path']));
        $this->assertGreaterThan(0, $package['size']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($package['path']) === true);
        $this->assertGreaterThan(0, $zip->numFiles);
        $zip->close();

        File::delete($package['path']);
    }

    public function test_super_admin_can_download_stored_package(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $packageDir = storage_path('app/packages');
        File::ensureDirectoryExists($packageDir);

        $filename = 'class-sync-application-1.0.0-test.zip';
        $path = $packageDir.DIRECTORY_SEPARATOR.$filename;
        File::put($path, 'test-package');

        $this->actingAs($superAdmin)
            ->get(route('settings.application-package.download', $filename))
            ->assertOk()
            ->assertDownload($filename);

        File::delete($path);
    }

    public function test_super_admin_can_download_desktop_installer_from_electron_output(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $distDir = base_path('electron/dist-new');
        File::ensureDirectoryExists($distDir);

        $filename = 'Class Sync Setup 1.0.0-test.exe';
        $path = $distDir.DIRECTORY_SEPARATOR.$filename;
        File::put($path, 'desktop-installer');

        $this->actingAs($superAdmin)
            ->get(route('settings.application-package.download', $filename))
            ->assertOk()
            ->assertDownload($filename);

        File::delete($path);
    }

    public function test_application_package_page_lists_electron_installer_for_download(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $distDir = base_path('electron/dist-new');
        File::ensureDirectoryExists($distDir);

        $filename = 'Class Sync Setup 1.0.0-test.exe';
        File::put($distDir.DIRECTORY_SEPARATOR.$filename, 'desktop-installer');

        $this->actingAs($superAdmin)
            ->get(route('settings.application-package'))
            ->assertOk()
            ->assertSee('Download Class Sync Desktop App')
            ->assertSee($filename)
            ->assertSee('Download Installer');

        File::delete($distDir.DIRECTORY_SEPARATOR.$filename);
    }

    public function test_super_admin_can_upload_desktop_icon(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $assetsDir = base_path('electron/assets');
        File::ensureDirectoryExists($assetsDir);

        foreach (['icon.png', 'icon.ico'] as $filename) {
            $path = $assetsDir.DIRECTORY_SEPARATOR.$filename;
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $png = imagecreatetruecolor(256, 256);
        $iconPath = $assetsDir.DIRECTORY_SEPARATOR.'upload-test.png';
        imagepng($png, $iconPath);
        imagedestroy($png);

        $service = app(ApplicationPackageService::class);
        $icon = $service->storeDesktopIcon(new \Illuminate\Http\UploadedFile(
            $iconPath,
            'upload-test.png',
            'image/png',
            null,
            true,
        ));

        $this->assertSame('icon.png', $icon['filename']);
        $this->assertTrue(File::exists($assetsDir.DIRECTORY_SEPARATOR.'icon.png'));

        File::delete($iconPath);
        File::delete($assetsDir.DIRECTORY_SEPARATOR.'icon.png');
    }

    public function test_super_admin_can_preview_desktop_icon(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $assetsDir = base_path('electron/assets');
        File::ensureDirectoryExists($assetsDir);
        File::put($assetsDir.DIRECTORY_SEPARATOR.'icon.png', 'fake-icon');

        $this->actingAs($superAdmin)
            ->get(route('settings.application-package.icon'))
            ->assertOk();

        File::delete($assetsDir.DIRECTORY_SEPARATOR.'icon.png');
    }
}
