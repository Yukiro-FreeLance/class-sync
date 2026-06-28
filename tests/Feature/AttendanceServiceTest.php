<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Services\Attendance\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $year = AcademicYear::factory()->create();
        $grade = GradeLevel::factory()->create();
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);

        $this->student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'status' => StudentStatus::Active,
        ]);
    }

    public function test_student_can_check_in_and_out(): void
    {
        $service = app(AttendanceService::class);

        $service->recordCheckIn($this->student);
        $this->assertTrue($service->isStudentInside($this->student->id));

        $service->recordCheckOut($this->student);
        $this->assertFalse($service->isStudentInside($this->student->id));
    }

    public function test_daily_stats_returns_counts(): void
    {
        AttendanceRecord::factory()->create([
            'student_id' => $this->student->id,
            'date' => today(),
            'status' => AttendanceStatus::Present,
            'time_in' => '07:30:00',
        ]);

        $stats = app(AttendanceService::class)->getDailyStats();

        $this->assertGreaterThan(0, $stats->sum());
    }
}
