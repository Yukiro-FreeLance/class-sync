<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\AcademicYear;
use App\Models\AttendancePeriodLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRemark;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Reports\ReportService;
use Database\Seeders\AttendanceRemarkSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AttendanceRemarkSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);
    }

    public function test_reports_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_attendance_summary_preview_includes_gate_and_class_data(): void
    {
        $today = Carbon::today()->toDateString();
        $grade = GradeLevel::factory()->create();
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);
        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ]);
        $remark = AttendanceRemark::query()->where('is_default', true)->first()
            ?? AttendanceRemark::query()->first();

        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => $today,
            'time_in' => '08:45:00',
            'status' => AttendanceStatus::Late,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        AttendancePeriodLog::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'attendance_remark_id' => $remark->id,
            'date' => $today,
        ]);

        $preview = app(ReportService::class)->preview(
            'attendance_summary',
            $today,
            $today,
        );

        $this->assertSame('Attendance Summary', $preview->title);
        $this->assertNotEmpty($preview->summaryStats);
        $this->assertNotEmpty($preview->rows);
        $lateCount = collect($preview->summaryStats)->firstWhere('label', 'Late (gate)')['value'] ?? 0;
        $this->assertGreaterThan(0, $lateCount);
    }

    public function test_late_arrivals_report_lists_late_records(): void
    {
        $today = Carbon::today()->toDateString();
        $student = Student::factory()->create(['last_name' => 'LateStudent']);

        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => $today,
            'time_in' => '08:45:00',
            'status' => AttendanceStatus::Late,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $preview = app(ReportService::class)->preview(
            'late_arrivals',
            $today,
            $today,
        );

        $this->assertSame(1, $preview->totalRows);
        $this->assertStringContainsString('LateStudent', $preview->rows[0]['name']);
    }

    public function test_reports_livewire_renders_preview(): void
    {
        Student::factory()->create([
            'last_name' => 'PreviewTest',
            'status' => StudentStatus::Active,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ReportsIndex::class)
            ->set('reportType', 'student_list')
            ->assertSee('Student List')
            ->assertSee('PreviewTest');
    }

    public function test_admin_can_export_report(): void
    {
        AcademicYear::factory()->create(['is_current' => true]);
        Student::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('reports.export', [
                'report_type' => 'student_list',
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
                'format' => 'xlsx',
            ]))
            ->assertOk()
            ->assertDownload();
    }
}
