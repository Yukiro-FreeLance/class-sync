<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEditAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_can_edit_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(UserRole::Teacher->value);

        $this->assertTrue($admin->can('update', $target));

        $this->actingAs($admin)
            ->get(route('settings.users.edit', $target))
            ->assertOk();
    }

    public function test_super_admin_can_edit_user(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $target = User::factory()->create(['is_active' => true]);
        $target->assignRole(UserRole::Teacher->value);

        $this->actingAs($superAdmin)
            ->get(route('settings.users.edit', $target))
            ->assertOk();
    }

    public function test_administrator_cannot_edit_super_admin_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('settings.users.edit', $superAdmin))
            ->assertForbidden();
    }

    public function test_administrator_cannot_see_super_admin_in_user_list(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $superAdmin = User::factory()->create([
            'is_active' => true,
            'first_name' => 'Hidden',
            'last_name' => 'SuperAdmin',
            'username' => 'hidden_super',
        ]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertDontSee('Hidden SuperAdmin')
            ->assertDontSee('hidden_super');
    }
}
