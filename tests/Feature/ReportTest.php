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

    public function test_research_attendance_report_writes_narrative_and_charts(): void
    {
        $service = app(ReportService::class);

        $this->assertSame('Very Low', $service->interpretAttendanceRate(77.29)['interpretation']);
        $this->assertSame('High', $service->interpretAttendanceRate(90)['interpretation']);
        $this->assertSame('Very High', $service->interpretAttendanceRate(95)['interpretation']);
        $this->assertSame('Moderate', $service->interpretAttendanceRate(85)['interpretation']);
        $this->assertSame('Low', $service->interpretAttendanceRate(80)['interpretation']);
        $this->assertSame('Very Low', $service->interpretAttendanceRate(79.99)['interpretation']);

        $from = Carbon::today()->subDays(3)->toDateString();
        $to = Carbon::today()->toDateString();
        $grade7 = GradeLevel::factory()->create(['name' => 'Grade 7', 'sort_order' => 7]);
        $grade8 = GradeLevel::factory()->create(['name' => 'Grade 8', 'sort_order' => 8]);
        $section7 = Section::factory()->create(['grade_level_id' => $grade7->id, 'name' => 'Rizal']);
        $section8 = Section::factory()->create(['grade_level_id' => $grade8->id, 'name' => 'Mabini']);
        $present = AttendanceRemark::query()->where('code', 'present')->first();
        $absent = AttendanceRemark::query()->where('code', 'absent')->first();

        $students = [
            Student::factory()->create([
                'grade_level_id' => $grade7->id,
                'section_id' => $section7->id,
                'last_name' => 'Alpha',
            ]),
            Student::factory()->create([
                'grade_level_id' => $grade8->id,
                'section_id' => $section8->id,
                'last_name' => 'Beta',
            ]),
        ];

        foreach ($students as $student) {
            foreach (range(0, 2) as $offset) {
                AttendancePeriodLog::query()->create([
                    'student_id' => $student->id,
                    'section_id' => $student->section_id,
                    'attendance_remark_id' => $present->id,
                    'date' => Carbon::today()->subDays($offset)->toDateString(),
                ]);
            }

            AttendancePeriodLog::query()->create([
                'student_id' => $student->id,
                'section_id' => $student->section_id,
                'attendance_remark_id' => $absent->id,
                'date' => Carbon::today()->subDays(3)->toDateString(),
            ]);
        }

        $preview = $service->preview('research_attendance', $from, $to, [], [
            'study_context' => 'before the implementation of the LAKBAY-GABAY Program',
        ]);

        $this->assertSame('Research Attendance Report', $preview->title);
        $this->assertStringContainsString('2 identified learners', $preview->narrative);
        $this->assertStringContainsString('before the implementation of the LAKBAY-GABAY Program', $preview->narrative);
        $this->assertStringContainsString('75.00%', $preview->narrative);
        $this->assertStringContainsString('classified as Very Low', $preview->narrative);
        $this->assertStringContainsString('Present accounted for 75.00%', $preview->narrative);
        $this->assertStringContainsString('Absent for 25.00%', $preview->narrative);
        $this->assertStringContainsString('Grade 7 (1 learner)', $preview->narrative);
        $this->assertStringContainsString('Grade 8 (1)', $preview->narrative);
        $this->assertCount(4, $preview->charts);
        $this->assertSame('Attendance rate by grade', $preview->charts[0]['title']);
        $this->assertSame([75.0, 75.0], $preview->charts[0]['datasets'][0]['data']);
        $this->assertSame([1, 1], $preview->charts[1]['datasets'][0]['data']);
        $this->assertSame('Attendance status percentages', $preview->charts[2]['title']);
        $this->assertCount(2, $preview->rows);
        $this->assertSame('Very Low', $preview->rows[0]['interpretation']);
        $this->assertSame('75.00% (3)', $preview->rows[0]['present_share']);
        $this->assertSame('25.00% (1)', $preview->rows[0]['absent_share']);

        $table = collect($preview->tables)->firstWhere('title', 'Table 1. Attendance rate by grade level');
        $this->assertNotNull($table);
        $this->assertSame('Grade 7', $table['rows'][0]['grade']);
        $this->assertSame(1, $table['rows'][0]['learners']);
        $statusTable = collect($preview->tables)->firstWhere('title', 'Table 2. Attendance status percentages');
        $this->assertNotNull($statusTable);
        $this->assertSame('75.00%', collect($statusTable['rows'])->firstWhere('status', 'Present')['percentage']);
        $scale = collect($preview->tables)->firstWhere('title', 'Table 3. Adopted attendance interpretation');
        $this->assertNotNull($scale);
        $this->assertSame('Adopted from Delfin (2019).', $scale['note']);
        $this->assertSame('95%–100%', $scale['rows'][0]['range']);
        $this->assertSame('Very High', $scale['rows'][0]['interpretation']);
        $this->assertSame('Below 80%', $scale['rows'][4]['range']);

        Livewire::actingAs($this->admin)
            ->test(ReportsIndex::class)
            ->set('reportType', 'research_attendance')
            ->set('dateFrom', $from)
            ->set('dateTo', $to)
            ->set('studyContext', 'before the implementation of the LAKBAY-GABAY Program')
            ->assertSee('Attendance rate by grade')
            ->assertSee('Identified learners by grade')
            ->assertSee('Table 1. Attendance rate by grade level')
            ->assertSee('Table 2. Attendance status percentages')
            ->assertSee('Table 4. Attendance of identified learners')
            ->assertSee('classified as Very Low')
            ->assertSee('Adopted from Delfin (2019).')
            ->assertSee('LAKBAY-GABAY Program');
    }

    public function test_research_attendance_report_uses_gate_records_when_class_logs_are_absent(): void
    {
        $today = Carbon::today()->toDateString();
        $grade = GradeLevel::factory()->create(['name' => 'Grade 10', 'sort_order' => 10]);
        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'last_name' => 'GateOnly',
        ]);

        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => $today,
            'time_in' => '07:30:00',
            'status' => AttendanceStatus::Present,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);
        AttendanceRecord::query()->create([
            'student_id' => $student->id,
            'user_id' => $this->admin->id,
            'date' => Carbon::yesterday()->toDateString(),
            'time_in' => '08:00:00',
            'status' => AttendanceStatus::Absent,
            'method' => \App\Enums\AttendanceMethod::Manual,
        ]);

        $preview = app(ReportService::class)->preview(
            'research_attendance',
            Carbon::yesterday()->toDateString(),
            $today,
        );

        $this->assertStringContainsString('1 identified learner', $preview->narrative);
        $this->assertStringContainsString('50.00%', $preview->narrative);
        $this->assertStringContainsString('classified as Very Low', $preview->narrative);
        $this->assertStringContainsString('Grade 10 (1 learner)', $preview->narrative);
        $this->assertStringContainsString('gate check-ins', $preview->narrativeNote);
        $this->assertStringContainsString('GateOnly', $preview->rows[0]['name']);
    }

    public function test_reports_month_filter_covers_the_full_month(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ReportsIndex::class)
            ->set('periodMode', 'month')
            ->set('month', '2026-09')
            ->assertSet('dateFrom', '2026-09-01')
            ->assertSet('dateTo', '2026-09-30')
            ->set('reportType', 'research_attendance')
            ->set('studyContext', 'before the implementation of the LAKBAY-GABAY Program')
            ->assertSee('Research Attendance Report')
            ->assertSee('Table 3. Adopted attendance interpretation')
            ->assertSee('95%–100%')
            ->assertSee('Moderate')
            ->assertSee('Below 80%')
            ->assertSee('Delfin (2019)')
            ->assertSee('LAKBAY-GABAY');
    }

    public function test_research_report_status_filter_recalculates_percentages(): void
    {
        $grade = GradeLevel::factory()->create(['name' => 'Grade 9', 'sort_order' => 9]);
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);
        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
        ]);
        $present = AttendanceRemark::query()->where('code', 'present')->first();
        $late = AttendanceRemark::query()->where('code', 'late')->first();
        $absent = AttendanceRemark::query()->where('code', 'absent')->first();

        foreach ([$present, $present, $present, $late, $absent] as $index => $remark) {
            AttendancePeriodLog::query()->create([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'attendance_remark_id' => $remark->id,
                'date' => Carbon::today()->subDays($index)->toDateString(),
            ]);
        }

        $preview = app(ReportService::class)->preview(
            'research_attendance',
            Carbon::today()->subDays(4)->toDateString(),
            Carbon::today()->toDateString(),
            [],
            ['statuses' => ['present', 'absent']],
        );

        $this->assertStringContainsString('75.00%', $preview->narrative);
        $this->assertStringContainsString('classified as Very Low', $preview->narrative);
        $this->assertStringContainsString('Present accounted for 75.00%', $preview->narrative);
        $this->assertStringContainsString('Absent for 25.00%', $preview->narrative);

        $statusTable = collect($preview->tables)->firstWhere('title', 'Table 2. Attendance status percentages');
        $this->assertCount(2, $statusTable['rows']);
        $this->assertNull(collect($statusTable['rows'])->firstWhere('status', 'Late'));
    }
}
