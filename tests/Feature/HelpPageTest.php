<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Help\UserManualService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authenticated_user_can_view_help_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::Teacher->value);

        $this->actingAs($user)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Help Center')
            ->assertSee('User Manual')
            ->assertSee('FAQ');
    }

    public function test_guest_cannot_view_help_page(): void
    {
        $this->get(route('help.index'))
            ->assertRedirect();
    }

    public function test_manual_service_provides_sections_and_faq(): void
    {
        $manual = app(UserManualService::class);

        $this->assertNotEmpty($manual->manualSections());
        $this->assertNotEmpty($manual->faqItems());
        $this->assertContains('Students', collect($manual->manualSections())->pluck('title')->all());
    }

    public function test_help_page_can_switch_to_faq_tab(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(UserRole::Teacher->value);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Help\Index::class)
            ->set('activeTab', 'faq')
            ->assertSee('Does Class Sync work without internet?');
    }
}
