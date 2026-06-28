<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Navigation\SidebarNavigationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected SidebarNavigationService $navigation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->navigation = app(SidebarNavigationService::class);
    }

    public function test_teacher_sees_limited_sidebar_items(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $routes = collect($this->navigation->itemsFor($teacher))->pluck('route')->filter()->all();

        $this->assertContains('dashboard', $routes);
        $this->assertContains('students.index', $routes);
        $this->assertContains('attendance.index', $routes);
        $this->assertContains('reports.index', $routes);
        $this->assertNotContains('teachers.index', $routes);
        $this->assertNotContains('settings.users.index', $routes);
        $this->assertNotContains('settings.academic.structure', $routes);
    }

    public function test_administrator_sees_full_sidebar(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $routes = collect($this->navigation->itemsFor($admin))->pluck('route')->filter()->all();

        $this->assertContains('settings.users.index', $routes);
        $this->assertContains('settings.academic.structure', $routes);
        $this->assertContains('audit-logs.index', $routes);
        $this->assertNotContains('settings.application-package', $routes);
    }

    public function test_super_admin_sees_desktop_app_sidebar_item(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $routes = collect($this->navigation->itemsFor($superAdmin))->pluck('route')->filter()->all();

        $this->assertContains('settings.application-package', $routes);
    }

    public function test_security_sees_attendance_but_not_reports(): void
    {
        $security = User::factory()->create(['is_active' => true]);
        $security->assignRole(UserRole::Security->value);

        $routes = collect($this->navigation->itemsFor($security))->pluck('route')->filter()->all();

        $this->assertContains('attendance.index', $routes);
        $this->assertNotContains('reports.index', $routes);
    }

    public function test_registrar_sees_students_and_settings_but_not_users(): void
    {
        $registrar = User::factory()->create(['is_active' => true]);
        $registrar->assignRole(UserRole::Registrar->value);

        $routes = collect($this->navigation->itemsFor($registrar))->pluck('route')->filter()->all();

        $this->assertContains('students.index', $routes);
        $this->assertContains('settings.general', $routes);
        $this->assertNotContains('settings.users.index', $routes);
        $this->assertNotContains('settings.academic.structure', $routes);
    }
}
