<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Settings\SubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_view_subscription_settings(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('settings.subscription'))
            ->assertOk()
            ->assertSee('System Subscription')
            ->assertSee('Time Bomb Expiry');
    }

    public function test_administrator_cannot_view_subscription_settings(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $this->actingAs($admin)
            ->get(route('settings.subscription'))
            ->assertForbidden();
    }

    public function test_super_admin_can_save_subscription_expiry(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $expiresAt = now()->addDays(30)->toDateString();

        Livewire::actingAs($superAdmin)
            ->test(\App\Livewire\Settings\Subscription::class)
            ->set('expires_at', $expiresAt)
            ->set('message', 'Please renew your Class Sync license.')
            ->call('save')
            ->assertHasNoErrors();

        $status = app(SubscriptionService::class)->status();

        $this->assertTrue($status['is_configured']);
        $this->assertTrue($status['is_active']);
        $this->assertSame($expiresAt, $status['expires_at']);
        $this->assertSame('Please renew your Class Sync license.', $status['message']);
    }

    public function test_normal_user_is_redirected_when_subscription_expires(): void
    {
        app(SubscriptionService::class)->update(now()->subDay()->toDateString(), 'Subscription ended.');

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.expired'));

        $this->actingAs($teacher)
            ->get(route('subscription.expired'))
            ->assertStatus(503)
            ->assertSee('This page is down')
            ->assertSee('Subscription ended.');
    }

    public function test_super_admin_can_still_access_app_when_subscription_expires(): void
    {
        app(SubscriptionService::class)->update(now()->subDay()->toDateString());

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->get(route('settings.subscription'))
            ->assertOk()
            ->assertSee('Expired');
    }

    public function test_active_subscription_allows_normal_users(): void
    {
        app(SubscriptionService::class)->update(now()->addMonth()->toDateString());

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_normal_user_sees_hosting_expiry_banner_when_subscription_is_active(): void
    {
        $expiresAt = now()->addDays(14);
        app(SubscriptionService::class)->update($expiresAt->toDateString());

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Hosting will end on')
            ->assertSee($expiresAt->format('M j, Y'))
            ->assertSee('Please update your system admin about possible downtime.')
            ->assertSee('I understand')
            ->assertSee('Remind me later');
    }

    public function test_super_admin_does_not_see_hosting_expiry_banner(): void
    {
        app(SubscriptionService::class)->update(now()->addDays(14)->toDateString());

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Hosting will end on')
            ->assertDontSee('Please update your system admin about possible downtime.');
    }

    public function test_normal_user_does_not_see_banner_without_subscription_expiry(): void
    {
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Hosting will end on');
    }

    public function test_clearing_expiry_restores_access(): void
    {
        app(SubscriptionService::class)->update(now()->subDay()->toDateString());

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        Livewire::actingAs($superAdmin)
            ->test(\App\Livewire\Settings\Subscription::class)
            ->call('clearExpiry')
            ->assertHasNoErrors();

        $this->assertTrue(app(SubscriptionService::class)->isActive());
        $this->assertFalse(app(SubscriptionService::class)->isConfigured());

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_super_admin_sees_subscription_in_sidebar(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $routes = collect(app(\App\Services\Navigation\SidebarNavigationService::class)->itemsFor($superAdmin))
            ->pluck('route')
            ->filter()
            ->all();

        $this->assertContains('settings.subscription', $routes);
    }

    public function test_administrator_does_not_see_subscription_in_sidebar(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $routes = collect(app(\App\Services\Navigation\SidebarNavigationService::class)->itemsFor($admin))
            ->pluck('route')
            ->filter()
            ->all();

        $this->assertNotContains('settings.subscription', $routes);
    }
}
