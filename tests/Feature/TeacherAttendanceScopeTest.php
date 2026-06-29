<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Livewire\Attendance\Bulk;
use App\Models\AcademicYear;
use App\Models\AttendanceRemark;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\TeacherScopeService;
use App\Services\Attendance\AttendancePeriodService;
use Database\Seeders\AttendanceRemarkSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherAttendanceScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $otherTeacher;

    protected Section $section;

    protected ClassSchedule $ownSchedule;

    protected ClassSchedule $otherSchedule;

    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AttendanceRemarkSeeder::class);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole(UserRole::Teacher->value);

        $this->otherTeacher = User::factory()->create(['is_active' => true]);
        $this->otherTeacher->assignRole(UserRole::Teacher->value);

        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-scope',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['department_id' => $department->id]);
        $this->section = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $math = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Mathematics',
            'code' => 'MATH7',
            'is_active' => true,
        ]);

        $science = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Science',
            'code' => 'SCI7',
            'is_active' => true,
        ]);

        $this->date = now()->toDateString();
        $day = DayOfWeek::from(now()->dayOfWeekIso);

        $this->ownSchedule = ClassSchedule::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $math->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => $day,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        $this->otherSchedule = ClassSchedule::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $science->id,
            'teacher_id' => $this->otherTeacher->id,
            'semester' => Semester::First,
            'day_of_week' => $day,
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
        ]);
    }

    public function test_teacher_only_sees_own_class_schedules(): void
    {
        $scope = app(TeacherScopeService::class);

        $schedules = $scope->accessibleClassSchedules(
            $this->teacher,
            $this->section->id,
            $this->date,
        );

        $this->assertCount(1, $schedules);
        $this->assertTrue($schedules->contains('id', $this->ownSchedule->id));
        $this->assertFalse($schedules->contains('id', $this->otherSchedule->id));
    }

    public function test_teacher_cannot_save_attendance_for_another_teachers_schedule(): void
    {
        $student = Student::factory()->create([
            'section_id' => $this->section->id,
        ]);

        $presentId = AttendanceRemark::query()->where('code', 'present')->value('id');

        $this->expectException(AuthorizationException::class);

        app(AttendancePeriodService::class)->bulkSave(
            $this->section->id,
            $this->otherSchedule->id,
            $this->date,
            [$student->id => ['remark_id' => $presentId, 'remarks' => null, 'went_out' => false]],
            $this->teacher->id,
        );
    }

    public function test_teacher_can_save_attendance_for_own_schedule(): void
    {
        $student = Student::factory()->create([
            'section_id' => $this->section->id,
        ]);

        $presentId = AttendanceRemark::query()->where('code', 'present')->value('id');

        $result = app(AttendancePeriodService::class)->bulkSave(
            $this->section->id,
            $this->ownSchedule->id,
            $this->date,
            [$student->id => ['remark_id' => $presentId, 'remarks' => null, 'went_out' => false]],
            $this->teacher->id,
        );

        $this->assertSame(1, $result['saved']);
    }

    public function test_teacher_attendance_page_defaults_to_class_session(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertDontSee('Campus Gate')
            ->assertSee('Class Session');
    }

    public function test_teacher_bulk_attendance_only_lists_own_schedule(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(Bulk::class)
            ->set('section', (string) $this->section->id)
            ->set('date', $this->date)
            ->assertSet('classScheduleId', $this->ownSchedule->id)
            ->assertSee('Mathematics')
            ->assertDontSee('Science');
    }

    public function test_admin_acting_as_teacher_can_access_all_class_schedules_for_attendance(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'acts_as_teacher' => true]);
        $admin->assignRole(UserRole::Administrator->value);
        $admin->assignRole(UserRole::Teacher->value);

        $scope = app(TeacherScopeService::class);

        $this->assertFalse($scope->bypassesScope($admin));
        $this->assertTrue($scope->bypassesAttendanceScope($admin));
        $this->assertFalse($scope->isAttendanceScoped($admin));

        $schedules = $scope->accessibleClassSchedules(
            $admin,
            $this->section->id,
            $this->date,
        );

        $this->assertCount(2, $schedules);
    }
}
