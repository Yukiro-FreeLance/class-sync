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
}
