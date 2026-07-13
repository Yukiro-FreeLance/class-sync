<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Students\Index as StudentsIndex;
use Tests\TestCase;

class StudentsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_students_index(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('administrator');

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk();
    }

    public function test_user_without_students_view_permission_gets_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('security');

        $this->actingAs($user)
            ->get(route('students.index'))
            ->assertForbidden();
    }

    public function test_teacher_sees_all_students_regardless_of_class_assignment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-students-index',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['department_id' => $department->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $assignedSection = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Assigned',
        ]);

        $studentSection = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Other',
        ]);

        $subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Science',
            'code' => 'SCI-IDX',
            'is_active' => true,
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $assignedSection->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
            'semester' => 'first',
        ]);

        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $studentSection->id,
            'academic_year_id' => $academicYear->id,
            'first_name' => 'Visible',
            'last_name' => 'Student',
        ]);

        Livewire::actingAs($teacher)
            ->test(StudentsIndex::class)
            ->assertSee($student->list_name);
    }
}
