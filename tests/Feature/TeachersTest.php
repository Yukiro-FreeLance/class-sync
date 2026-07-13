<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Teachers\Create;
use App\Livewire\Teachers\Show;
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
use Tests\TestCase;

class TeachersTest extends TestCase
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

    public function test_admin_can_view_teachers_list(): void
    {
        $this->actingAs($this->admin)
            ->get(route('teachers.index'))
            ->assertOk();
    }

    public function test_admin_can_create_teacher(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Create::class)
            ->set('first_name', 'Ana')
            ->set('last_name', 'Cruz')
            ->set('username', 'anacruz')
            ->set('email', 'ana@school.local')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertRedirect(route('teachers.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'anacruz',
            'email' => 'ana@school.local',
        ]);

        $teacher = User::query()->where('username', 'anacruz')->first();
        $this->assertTrue($teacher->hasRole(UserRole::Teacher->value));
    }

    public function test_admin_can_view_teacher_students(): void
    {
        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-teachers',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['department_id' => $department->id]);

        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->assignRole(UserRole::Teacher->value);

        $section = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'adviser_id' => $teacher->id,
        ]);

        $subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Mathematics',
            'code' => 'MATH-TEACH',
            'is_active' => true,
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
            'semester' => 'first',
        ]);

        $student = Student::factory()->create([
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->full_name)
            ->assertSee($student->list_name);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['teacher' => $teacher])
            ->assertSee($student->student_number);
    }
}
