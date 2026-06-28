<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Settings\Users\Create;
use App\Models\Section;
use App\Models\User;
use App\Services\Academic\TeacherScopeService;
use App\Services\Users\UserAccessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAccessSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);
    }

    public function test_admin_can_view_users_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.users.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('settings.users.roles'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_roles_settings(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('settings.users.roles'))
            ->assertOk();
    }

    public function test_admin_can_be_enabled_to_act_as_teacher(): void
    {
        $section = Section::factory()->create(['adviser_id' => $this->admin->id]);

        $this->admin->update(['acts_as_teacher' => true]);
        $this->admin->assignRole(UserRole::Teacher->value);

        $scope = app(TeacherScopeService::class);

        $this->assertFalse($scope->bypassesScope($this->admin));
        $this->assertContains($section->id, $scope->accessibleSectionIds($this->admin));
    }

    public function test_teacher_can_access_attendance_page(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('attendance.index'))
            ->assertOk();

        $this->actingAs($teacher)
            ->get(route('attendance.bulk'))
            ->assertOk();
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Create::class)
            ->set('first_name', 'Maria')
            ->set('last_name', 'Reyes')
            ->set('username', 'mariareyes')
            ->set('email', 'maria@school.local')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('selectedRoles', [UserRole::Registrar->value])
            ->call('save')
            ->assertRedirect(route('settings.users.index'));

        $user = User::query()->where('username', 'mariareyes')->first();
        $this->assertTrue($user->hasRole(UserRole::Registrar->value));
    }

    public function test_role_permissions_can_be_updated(): void
    {
        app(UserAccessService::class)->syncRolePermissions(UserRole::Teacher->value, [
            'students.view',
            'attendance.view',
        ]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->assertTrue($teacher->can('students.view'));
        $this->assertFalse($teacher->can('students.create'));
    }

    public function test_super_admin_has_unrestricted_access(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->hasUnrestrictedAccess());
        $this->assertTrue($superAdmin->can('users.delete'));
    }

    public function test_disabled_role_is_not_assignable(): void
    {
        app(UserAccessService::class)->setRoleEnabled(UserRole::Registrar->value, false);

        $roles = app(UserAccessService::class)->assignableRolesFor($this->admin);

        $this->assertFalse(
            collect($roles)->contains(fn (UserRole $role) => $role === UserRole::Registrar),
        );
    }

    public function test_protected_roles_cannot_be_disabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(UserAccessService::class)->setRoleEnabled(UserRole::SuperAdmin->value, false);
    }
}
