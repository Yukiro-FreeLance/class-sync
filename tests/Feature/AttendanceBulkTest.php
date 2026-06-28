<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AttendanceRemark;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Attendance\AttendancePeriodService;
use Database\Seeders\AttendanceRemarkSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_class_attendance_saves_for_scheduled_subject(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AttendanceRemarkSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(UserRole::Administrator->value);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-bulk',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['department_id' => $department->id]);
        $section = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Science',
            'code' => 'SCI7',
            'is_active' => true,
        ]);

        $date = now()->toDateString();
        $schedule = ClassSchedule::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::from(now()->dayOfWeekIso),
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        $students = Student::factory()->count(2)->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $presentId = AttendanceRemark::query()->where('code', 'present')->value('id');

        $entries = [];
        foreach ($students as $student) {
            $entries[$student->id] = ['remark_id' => $presentId, 'remarks' => null, 'went_out' => false];
        }

        $result = app(AttendancePeriodService::class)->bulkSave(
            $section->id,
            $schedule->id,
            $date,
            $entries,
            $admin->id,
        );

        $this->assertSame(2, $result['saved']);
        $this->assertDatabaseCount('attendance_period_logs', 2);
        $this->assertDatabaseHas('attendance_period_logs', [
            'class_schedule_id' => $schedule->id,
            'section_id' => $section->id,
        ]);
    }
}
