<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AuditAction;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_counts_unmarked_students_as_absent_and_lists_them(): void
    {
        $grade = GradeLevel::factory()->create();
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);

        Student::factory()->count(3)->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'status' => StudentStatus::Active,
        ]);

        $data = app(DashboardService::class)->data();

        $this->assertSame(3, $data['stats']['total_students']);
        $this->assertSame(3, $data['stats']['not_recorded']);
        $this->assertSame(3, $data['stats']['absent']);
        $this->assertCount(3, $data['topAbsentees']);
        $this->assertNotEmpty($data['detailedReport']);
    }

    public function test_dashboard_present_count_uses_status_not_check_in_only(): void
    {
        $student = Student::factory()->create(['status' => StudentStatus::Active]);

        \App\Models\AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'time_in' => '07:30:00',
            'status' => AttendanceStatus::Present,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $data = app(DashboardService::class)->data();

        $this->assertSame(1, $data['stats']['present']);
        $this->assertSame(0, $data['stats']['not_recorded']);
    }

    public function test_dashboard_livewire_renders_accurate_stats(): void
    {
        Student::factory()->create(['status' => StudentStatus::Active, 'last_name' => 'DashTest']);

        Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->assertSee('DashTest')
            ->assertSee('not yet recorded')
            ->assertSee('Detailed Attendance Report');
    }

    public function test_dashboard_includes_class_attendance_when_gate_records_missing(): void
    {
        $this->seed(\Database\Seeders\AttendanceRemarkSeeder::class);

        $student = Student::factory()->create(['status' => StudentStatus::Active]);
        $presentId = \App\Models\AttendanceRemark::query()->where('code', 'present')->value('id');

        \App\Models\AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'class_schedule_id' => null,
            'section_id' => null,
            'attendance_remark_id' => $presentId,
            'date' => now()->toDateString(),
        ]);

        $data = app(DashboardService::class)->data();

        $this->assertSame(1, $data['stats']['present']);
        $this->assertSame(1, $data['stats']['recorded_today']);
        $this->assertSame(0, $data['stats']['not_recorded']);
    }

    public function test_dashboard_filters_by_department_level_and_section(): void
    {
        $departmentA = Department::query()->create([
            'name' => 'Junior High',
            'code' => 'jhs-dash',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $departmentB = Department::query()->create([
            'name' => 'Senior High',
            'code' => 'shs-dash',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $gradeA = GradeLevel::factory()->create(['department_id' => $departmentA->id, 'name' => 'Grade 7']);
        $gradeB = GradeLevel::factory()->create(['department_id' => $departmentB->id, 'name' => 'Grade 11']);

        $sectionA = Section::factory()->create(['grade_level_id' => $gradeA->id, 'name' => 'Einstein']);
        $sectionB = Section::factory()->create(['grade_level_id' => $gradeB->id, 'name' => 'STEM-A']);

        Student::factory()->create([
            'grade_level_id' => $gradeA->id,
            'section_id' => $sectionA->id,
            'status' => StudentStatus::Active,
            'last_name' => 'OnlyInA',
        ]);

        Student::factory()->create([
            'grade_level_id' => $gradeB->id,
            'section_id' => $sectionB->id,
            'status' => StudentStatus::Active,
            'last_name' => 'OnlyInB',
        ]);

        $filtered = app(DashboardService::class)->data(now(), 'day', [
            'department' => $departmentA->id,
            'grade' => $gradeA->id,
            'section' => $sectionA->id,
        ]);

        $this->assertSame(1, $filtered['stats']['total_students']);
        $this->assertSame(1, $filtered['stats']['absent']);

        Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->set('department', (string) $departmentA->id)
            ->set('grade', (string) $gradeA->id)
            ->set('section', (string) $sectionA->id)
            ->assertSee('OnlyInA')
            ->assertDontSee('OnlyInB');
    }

    public function test_dashboard_week_period_builds_multi_day_report(): void
    {
        Student::factory()->create(['status' => StudentStatus::Active]);

        $data = app(DashboardService::class)->data(now(), 'week');

        $this->assertSame('week', $data['period']);
        $this->assertFalse($data['isSingleDay']);
        $this->assertGreaterThan(1, count($data['detailedReport']));
        $this->assertSame(
            now()->startOfWeek()->toDateString(),
            $data['rangeStart']->toDateString(),
        );
    }

    public function test_dashboard_month_period_and_clear_filters(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->set('period', 'month')
            ->set('date', now()->toDateString())
            ->assertSee('Detailed Attendance Report')
            ->call('clearFilters')
            ->assertSet('period', 'day')
            ->assertSet('department', '')
            ->assertSet('grade', '')
            ->assertSet('section', '');
    }

    public function test_dashboard_status_details_modal_lists_students(): void
    {
        $present = Student::factory()->create([
            'status' => StudentStatus::Active,
            'last_name' => 'PresentKid',
        ]);
        $absent = Student::factory()->create([
            'status' => StudentStatus::Active,
            'last_name' => 'AbsentKid',
        ]);

        \App\Models\AttendanceRecord::query()->create([
            'student_id' => $present->id,
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'time_in' => '07:15:00',
            'status' => AttendanceStatus::Present,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(DashboardIndex::class)
            ->call('openStatusDetails', 'present')
            ->assertSet('showDetailsModal', true)
            ->assertSet('detailsCategory', 'present')
            ->assertSee('Present students')
            ->assertSee('PresentKid');

        $presentDetails = app(DashboardService::class)->statusDetails('present', now(), 'day');
        $this->assertCount(1, $presentDetails['rows']);
        $this->assertSame($present->id, $presentDetails['rows'][0]['student_id']);

        $component
            ->call('openStatusDetails', 'absent')
            ->assertSet('detailsCategory', 'absent')
            ->assertSee('Absent students')
            ->assertSee('AbsentKid')
            ->call('closeStatusDetails')
            ->assertSet('showDetailsModal', false);

        $absentDetails = app(DashboardService::class)->statusDetails('absent', now(), 'day');
        $this->assertCount(1, $absentDetails['rows']);
        $this->assertSame($absent->id, $absentDetails['rows'][0]['student_id']);
    }

    public function test_dashboard_recent_activity_hides_super_admin_logs(): void
    {
        $superAdmin = User::factory()->create([
            'is_active' => true,
            'name' => 'Super Administrator',
        ]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $registrar = User::factory()->create([
            'is_active' => true,
            'name' => 'Campus Registrar',
        ]);
        $registrar->assignRole(UserRole::Registrar->value);

        AuditLog::query()->create([
            'user_id' => $superAdmin->id,
            'action' => AuditAction::Delete,
            'description' => 'Deleted application package.',
        ]);

        AuditLog::query()->create([
            'user_id' => $registrar->id,
            'action' => AuditAction::Create,
            'description' => 'Created student record.',
        ]);

        $data = app(DashboardService::class)->data();

        $this->assertTrue(
            $data['recentActivity']->contains(fn (AuditLog $log) => $log->user_id === $registrar->id),
        );
        $this->assertFalse(
            $data['recentActivity']->contains(fn (AuditLog $log) => $log->user_id === $superAdmin->id),
        );
    }
}
