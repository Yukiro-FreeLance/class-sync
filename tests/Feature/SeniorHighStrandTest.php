<?php

namespace Tests\Feature;

use App\Livewire\Settings\Academic\Sections;
use App\Livewire\Settings\Academic\Strands;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use App\Enums\UserRole;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeniorHighStrandTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Department $shsDepartment;

    protected GradeLevel $grade11;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DepartmentSeeder::class);
        $this->seed(GradeLevelSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $this->shsDepartment = Department::query()->where('code', 'shs')->firstOrFail();
        $this->grade11 = GradeLevel::query()->where('code', '11')->firstOrFail();
    }

    public function test_can_create_strand_for_senior_high_grade(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Strands::class)
            ->set('gradeLevelId', $this->grade11->id)
            ->set('name', 'Science, Technology, Engineering and Mathematics')
            ->set('code', 'STEM')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('courses', [
            'grade_level_id' => $this->grade11->id,
            'code' => 'STEM',
        ]);
    }

    public function test_senior_high_section_requires_strand(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => true]);

        Livewire::actingAs($this->admin)
            ->test(Sections::class)
            ->set('gradeLevelId', $this->grade11->id)
            ->set('academicYearId', $year->id)
            ->set('name', 'A')
            ->call('save')
            ->assertHasErrors(['courseId']);
    }

    public function test_can_save_senior_high_section_with_strand(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => true]);

        $strand = Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'Accountancy, Business and Management',
            'code' => 'ABM',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Sections::class)
            ->set('gradeLevelId', $this->grade11->id)
            ->set('courseId', $strand->id)
            ->set('academicYearId', $year->id)
            ->set('name', 'A')
            ->call('save')
            ->assertHasNoErrors();

        $section = Section::query()->first();

        $this->assertSame($strand->id, $section->course_id);
        $this->assertSame('Grade 11 ABM A', $section->display_label);
    }

    public function test_junior_high_section_does_not_require_strand(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => true]);

        $grade10 = GradeLevel::query()->where('code', '10')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(Sections::class)
            ->set('gradeLevelId', $grade10->id)
            ->set('academicYearId', $year->id)
            ->set('name', 'A')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sections', [
            'grade_level_id' => $grade10->id,
            'course_id' => null,
            'name' => 'A',
        ]);
    }

    public function test_strands_page_is_accessible_to_administrators(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.academic.strands'))
            ->assertOk();
    }

    public function test_schedules_show_strand_filter_for_senior_high(): void
    {
        Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'Science, Technology, Engineering and Mathematics',
            'code' => 'STEM',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Settings\Academic\Schedules::class)
            ->set('department', (string) $this->shsDepartment->id)
            ->assertSee('Strand');
    }

    public function test_can_quick_add_subject_from_schedule_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Settings\Academic\Schedules::class)
            ->call('openQuickAdd', 'subject')
            ->set('quickSubjectName', 'Oral Communication')
            ->set('quickSubjectCode', 'ORAL11')
            ->call('saveQuickSubject')
            ->assertHasNoErrors()
            ->assertSet('subjectId', fn ($id) => $id !== null);

        $this->assertDatabaseHas('subjects', [
            'code' => 'ORAL11',
            'name' => 'Oral Communication',
        ]);
    }

    public function test_bulk_enrollment_shows_strand_filter_for_senior_high(): void
    {
        Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'Science, Technology, Engineering and Mathematics',
            'code' => 'STEM',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Students\BulkEnroll::class)
            ->set('department', (string) $this->shsDepartment->id)
            ->assertSee('Strand');
    }

    public function test_bulk_enrollment_sections_use_display_label_with_strand(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => true]);

        $stem = Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'STEM',
            'code' => 'STEM',
        ]);

        $abm = Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'ABM',
            'code' => 'ABM',
        ]);

        Section::query()->create([
            'grade_level_id' => $this->grade11->id,
            'course_id' => $stem->id,
            'academic_year_id' => $year->id,
            'name' => 'A',
        ]);

        Section::query()->create([
            'grade_level_id' => $this->grade11->id,
            'course_id' => $abm->id,
            'academic_year_id' => $year->id,
            'name' => 'A',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Students\BulkEnroll::class)
            ->set('department', (string) $this->shsDepartment->id)
            ->set('grade', (string) $this->grade11->id)
            ->set('strand', (string) $stem->id)
            ->assertSee('Grade 11 STEM A')
            ->assertDontSee('Grade 11 ABM A');
    }

    public function test_attendance_shows_strand_filter_for_senior_high(): void
    {
        Course::query()->create([
            'grade_level_id' => $this->grade11->id,
            'name' => 'STEM',
            'code' => 'STEM',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Attendance\Bulk::class)
            ->set('department', (string) $this->shsDepartment->id)
            ->assertSee('Strand');
    }
}
