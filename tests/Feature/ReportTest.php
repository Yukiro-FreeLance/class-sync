<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
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
use App\Models\StudentEnrollment;
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

    public function test_enrollment_report_counts_students_per_year_level_and_section(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['name' => 'Grade 7']);
        $sectionA = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Rizal',
        ]);
        $sectionB = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Bonifacio',
        ]);

        foreach ([[$sectionA, 'male'], [$sectionA, 'female'], [$sectionB, 'male']] as [$section, $gender]) {
            $student = Student::factory()->create([
                'grade_level_id' => $grade->id,
                'section_id' => $section->id,
                'academic_year_id' => $academicYear->id,
                'gender' => $gender,
            ]);

            StudentEnrollment::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'grade_level_id' => $grade->id,
                'section_id' => $section->id,
                'status' => EnrollmentStatus::Enrolled,
                'enrollment_date' => now()->toDateString(),
            ]);
        }

        // A withdrawn enrollment should not be counted.
        $withdrawnStudent = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $sectionB->id,
            'academic_year_id' => $academicYear->id,
            'gender' => 'female',
        ]);
        StudentEnrollment::query()->create([
            'student_id' => $withdrawnStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $grade->id,
            'section_id' => $sectionB->id,
            'status' => EnrollmentStatus::Withdrawn,
            'enrollment_date' => now()->toDateString(),
        ]);

        $preview = app(ReportService::class)->preview('enrollment', now()->toDateString(), now()->toDateString());

        $this->assertSame('Enrollment Report', $preview->title);

        $enrolled = collect($preview->summaryStats)->firstWhere('label', 'Enrolled students')['value'] ?? 0;
        $this->assertSame(3, $enrolled);

        $this->assertCount(1, $preview->rows);
        $this->assertSame('Grade 7', $preview->rows[0]['grade']);
        $this->assertSame(3, $preview->rows[0]['total']);
        $this->assertSame(2, $preview->rows[0]['male']);
        $this->assertSame(1, $preview->rows[0]['female']);

        $sectionTable = collect($preview->tables)->firstWhere('title', 'Enrolled per section');
        $this->assertNotNull($sectionTable);
        $this->assertCount(2, $sectionTable['rows']);
        $rizal = collect($sectionTable['rows'])->firstWhere('section', 'Rizal');
        $this->assertSame(2, $rizal['total']);
    }

    public function test_enrollment_report_renders_in_livewire(): void
    {
        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['name' => 'Grade 8']);
        $section = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Mabini',
        ]);
        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'status' => EnrollmentStatus::Enrolled,
            'enrollment_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ReportsIndex::class)
            ->set('reportType', 'enrollment')
            ->assertSee('Enrollment Report')
            ->assertSee('Grade 8')
            ->assertSee('Mabini');
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
