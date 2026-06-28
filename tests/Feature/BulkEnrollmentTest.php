<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Livewire\Students\BulkEnroll;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\User;
use App\Services\Students\StudentEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BulkEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected AcademicYear $academicYear;

    protected GradeLevel $gradeLevel;

    protected Section $section;

    protected Subject $subject;

    protected ClassSchedule $classSchedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-bulk-enroll',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->gradeLevel = GradeLevel::factory()->create(['department_id' => $department->id]);
        $this->section = Section::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $this->subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Science',
            'code' => 'SCI7',
            'is_active' => true,
        ]);

        $this->classSchedule = ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'semester' => Semester::First,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);
    }

    public function test_bulk_enrollment_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('students.enrollment'))
            ->assertOk();
    }

    public function test_bulk_enroll_service_enrolls_multiple_students(): void
    {
        $students = Student::factory()->count(2)->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'section_id' => null,
        ]);

        $result = app(StudentEnrollmentService::class)->bulkEnroll(
            $students->pluck('id')->all(),
            [
                'academic_year_id' => $this->academicYear->id,
                'grade_level_id' => $this->gradeLevel->id,
                'section_id' => $this->section->id,
                'status' => EnrollmentStatus::Enrolled->value,
                'enrollment_date' => now()->toDateString(),
                'class_schedule_ids' => [$this->classSchedule->id],
            ],
        );

        $this->assertSame(2, $result->successCount());
        $this->assertDatabaseCount('student_enrollments', 2);
        $this->assertDatabaseHas('student_enrollment_classes', [
            'class_schedule_id' => $this->classSchedule->id,
        ]);
    }

    public function test_bulk_enroll_rejects_invalid_class_schedules(): void
    {
        $student = Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $otherSection = Section::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Section B',
        ]);

        $foreignSchedule = ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $otherSection->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => User::factory()->create()->id,
            'semester' => Semester::First,
            'day_of_week' => 2,
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
        ]);

        $this->expectException(ValidationException::class);

        app(StudentEnrollmentService::class)->bulkEnroll([$student->id], [
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'status' => EnrollmentStatus::Enrolled->value,
            'class_schedule_ids' => [$foreignSchedule->id],
        ]);
    }

    public function test_bulk_assign_subjects_updates_existing_enrollments(): void
    {
        $student = Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $enrollment = StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'status' => EnrollmentStatus::Enrolled,
            'enrollment_date' => now()->toDateString(),
        ]);

        app(StudentEnrollmentService::class)->bulkAssignSubjects(
            [$student->id],
            $this->academicYear->id,
            $this->section->id,
            [$this->classSchedule->id],
        );

        $this->assertDatabaseHas('student_enrollment_classes', [
            'student_enrollment_id' => $enrollment->id,
            'class_schedule_id' => $this->classSchedule->id,
        ]);
    }

    public function test_admin_can_bulk_enroll_via_livewire(): void
    {
        $students = Student::factory()->count(2)->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'section_id' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(BulkEnroll::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('department', (string) $this->gradeLevel->department_id)
            ->set('grade', (string) $this->gradeLevel->id)
            ->set('section', (string) $this->section->id)
            ->set('selectedStudentIds', $students->pluck('id')->all())
            ->set('selectedSubjectIds', [$this->subject->id])
            ->call('enrollBulk')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('student_enrollments', 2);
    }
}
