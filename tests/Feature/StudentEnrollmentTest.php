<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Livewire\Students\Enroll;
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
use Livewire\Livewire;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected AcademicYear $academicYear;

    protected GradeLevel $gradeLevel;

    protected Section $section;

    protected Subject $subject;

    protected User $teacher;

    protected ClassSchedule $classSchedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $department = Department::query()->where('code', 'jhs')->first()
            ?? Department::query()->create([
                'name' => 'Junior High School',
                'code' => 'jhs-test',
                'sort_order' => 2,
                'is_active' => true,
            ]);

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->gradeLevel = GradeLevel::factory()->create(['department_id' => $department->id]);
        $this->section = Section::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole(UserRole::Teacher->value);

        $this->subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Mathematics',
            'code' => 'MATH7',
            'is_active' => true,
        ]);

        $this->classSchedule = ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);
    }

    public function test_admin_can_open_enroll_page(): void
    {
        $student = Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('students.enroll', $student))
            ->assertOk();
    }

    public function test_enrollment_service_assigns_section_subjects_and_syncs_student(): void
    {
        $student = Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'section_id' => null,
        ]);

        $enrollment = app(StudentEnrollmentService::class)->enroll($student, [
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'status' => EnrollmentStatus::Enrolled->value,
            'enrollment_date' => now()->toDateString(),
            'class_schedule_ids' => [$this->classSchedule->id],
        ]);

        $this->assertInstanceOf(StudentEnrollment::class, $enrollment);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'section_id' => $this->section->id,
        ]);
        $this->assertDatabaseHas('student_enrollment_classes', [
            'student_enrollment_id' => $enrollment->id,
            'class_schedule_id' => $this->classSchedule->id,
        ]);

        $student->refresh();
        $this->assertSame($this->section->id, $student->section_id);
    }

    public function test_admin_can_save_enrollment_via_livewire(): void
    {
        $student = Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'section_id' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Enroll::class, ['student' => $student])
            ->set('department_id', $this->gradeLevel->department_id)
            ->set('grade_level_id', $this->gradeLevel->id)
            ->set('section_id', $this->section->id)
            ->set('class_schedule_ids', [$this->classSchedule->id])
            ->call('save')
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $student->id,
            'section_id' => $this->section->id,
        ]);
    }
}
