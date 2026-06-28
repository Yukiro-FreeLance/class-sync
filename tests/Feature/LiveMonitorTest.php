<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Livewire\Attendance\LiveMonitor;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\LiveMonitorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveMonitorTest extends TestCase
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

    public function test_live_monitor_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('attendance.monitor'))
            ->assertOk();
    }

    public function test_live_monitor_counts_unmarked_students_as_absent(): void
    {
        Student::factory()->count(3)->create(['status' => StudentStatus::Active]);

        $snapshot = app(LiveMonitorService::class)->snapshot();

        $this->assertSame(3, $snapshot['stats']['not_recorded']);
        $this->assertSame(3, $snapshot['stats']['absent']);
        $this->assertCount(3, $snapshot['notCheckedIn']);
    }

    public function test_live_monitor_shows_present_student_in_check_ins(): void
    {
        $student = Student::factory()->create([
            'status' => StudentStatus::Active,
            'last_name' => 'MonitorTest',
        ]);

        \App\Models\AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'time_in' => '07:30:00',
            'status' => AttendanceStatus::Present,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $snapshot = app(LiveMonitorService::class)->snapshot();

        $this->assertSame(1, $snapshot['stats']['present']);
        $this->assertSame(1, $snapshot['stats']['inside_campus']);
        $this->assertStringContainsString('MonitorTest', $snapshot['recentCheckIns']->first()['name']);
    }

    public function test_livewire_renders_monitor_data(): void
    {
        Student::factory()->create(['status' => StudentStatus::Active]);

        Livewire::actingAs($this->admin)
            ->test(LiveMonitor::class)
            ->assertSee('not checked in')
            ->assertSee('Inside Campus');
    }
}
