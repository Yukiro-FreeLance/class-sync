<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\ClassList;
use App\Livewire\Students\MasterList;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Students\StudentListService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected AcademicYear $academicYear;

    protected GradeLevel $gradeLevel;

    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $department = Department::query()->create([
            'name' => 'Senior High School',
            'code' => 'shs-lists',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->gradeLevel = GradeLevel::factory()->create(['department_id' => $department->id]);
        $this->section = Section::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'A',
        ]);
    }

    public function test_master_list_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('students.lists.master'))
            ->assertOk();
    }

    public function test_class_list_page_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('students.lists.class'))
            ->assertOk();
    }

    public function test_master_list_service_returns_students_by_grade(): void
    {
        Student::factory()->count(3)->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $students = app(StudentListService::class)->masterListQuery(
            $this->academicYear->id,
            $this->gradeLevel->id,
        )->get();

        $this->assertCount(3, $students);
    }

    public function test_class_list_service_returns_section_students(): void
    {
        Student::factory()->count(2)->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $otherSection = Section::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'B',
        ]);

        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $otherSection->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $students = app(StudentListService::class)->classListQuery(
            $this->academicYear->id,
            $this->section->id,
        )->get();

        $this->assertCount(2, $students);
    }

    public function test_master_list_livewire_shows_students_when_grade_selected(): void
    {
        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
            'last_name' => 'Zamora',
        ]);

        Livewire::actingAs($this->admin)
            ->test(MasterList::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('department', (string) $this->gradeLevel->department_id)
            ->set('grade', (string) $this->gradeLevel->id)
            ->assertSee('Zamora')
            ->assertSee('Showing');
    }

    public function test_class_list_livewire_shows_students_for_section(): void
    {
        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
            'last_name' => 'Reyes',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ClassList::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('department', (string) $this->gradeLevel->department_id)
            ->set('grade', (string) $this->gradeLevel->id)
            ->set('section', (string) $this->section->id)
            ->assertSee('Reyes')
            ->assertSee('Total Students');
    }

    public function test_student_list_groups_and_sorts_by_gender_then_name(): void
    {
        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
            'gender' => 'female',
            'last_name' => 'Alpha',
            'first_name' => 'Anna',
        ]);

        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
            'gender' => 'male',
            'last_name' => 'Zulu',
            'first_name' => 'Zed',
        ]);

        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
            'gender' => 'male',
            'last_name' => 'Bravo',
            'first_name' => 'Ben',
        ]);

        $groups = StudentListService::groupByGender(
            app(StudentListService::class)->classListQuery(
                $this->academicYear->id,
                $this->section->id,
            )->get(),
        );

        $this->assertSame(['male', 'female'], $groups->keys()->all());
        $this->assertSame(['Bravo', 'Zulu'], $groups['male']->pluck('last_name')->all());
        $this->assertSame(['Alpha'], $groups['female']->pluck('last_name')->all());

        Livewire::actingAs($this->admin)
            ->test(ClassList::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('department', (string) $this->gradeLevel->department_id)
            ->set('grade', (string) $this->gradeLevel->id)
            ->set('section', (string) $this->section->id)
            ->assertSee('Male (2)')
            ->assertSee('Female (1)');
    }

    public function test_master_list_export_downloads(): void
    {
        Student::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('students.lists.master.export', [
                'academic_year_id' => $this->academicYear->id,
                'grade' => $this->gradeLevel->id,
            ]))
            ->assertOk()
            ->assertDownload();
    }
}
