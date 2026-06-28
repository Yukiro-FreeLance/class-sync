<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Settings\General;
use App\Models\User;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
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

    public function test_sidebar_uses_school_name_and_subtitle_from_settings(): void
    {
        app(SettingsService::class)->setMany([
            'school_name' => 'Saravia National High School',
            'school_code' => 'SNHS',
            'app_subtitle' => 'Attendance System',
        ], 'general');

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Saravia National High School')
            ->assertSee('Attendance System')
            ->assertSee('SNHS');
    }

    public function test_admin_can_upload_logo_in_general_settings(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('school-logo.png', 120, 120);

        Livewire::actingAs($this->admin)
            ->test(General::class)
            ->set('school_name', 'Test High School')
            ->set('logo', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $branding = app(BrandingService::class);

        $this->assertNotNull($branding->logoPath());
        $this->assertNotNull($branding->logoUrl());
        Storage::disk('public')->assertExists($branding->logoPath());

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($branding->logoUrl(), false);
    }

    public function test_admin_can_remove_logo(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('logo.png')->store('school', 'public');
        app(SettingsService::class)->set('logo_path', $path, 'general');

        Livewire::actingAs($this->admin)
            ->test(General::class)
            ->call('removeLogo')
            ->assertHasNoErrors();

        $this->assertNull(app(BrandingService::class)->logoPath());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_save_layout_colors(): void
    {
        Livewire::actingAs($this->admin)
            ->test(General::class)
            ->set('school_name', 'Test High School')
            ->set('sidebar_color', '#1e3a5f')
            ->set('header_color', '#f0f4f8')
            ->set('background_color', '#e8eef5')
            ->call('save')
            ->assertHasNoErrors();

        $branding = app(BrandingService::class);

        $this->assertSame('#1e3a5f', $branding->sidebarColor());
        $this->assertSame('#f0f4f8', $branding->headerColor());
        $this->assertSame('#e8eef5', $branding->backgroundColor());

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('--app-sidebar-bg: #1e3a5f', false);
    }

    public function test_branding_service_falls_back_to_school_group_logo(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('legacy-logo.png')->store('school', 'public');
        app(SettingsService::class)->set('logo_path', $path, 'school');

        $this->assertSame($path, app(BrandingService::class)->logoPath());
    }
}
