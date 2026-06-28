<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markAsNotInstalled();
    }

    public function test_setup_page_is_accessible_when_not_installed(): void
    {
        $response = $this->get('/setup');

        $response->assertStatus(200);
    }

    public function test_dashboard_redirects_to_setup_when_not_installed(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => UserRole::Administrator->value, 'guard_name' => 'web']);
        $user->assignRole(UserRole::Administrator->value);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('setup.index'));
    }
}
