<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Semester;
use App\Enums\UserRole;
use App\Livewire\Settings\Academic\Schedules;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\ClassScheduleConflictService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Section $section;

    protected Subject $subject;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole(UserRole::Teacher->value);

        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-sched',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $grade = GradeLevel::factory()->create(['department_id' => $department->id]);
        $this->section = Section::factory()->create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->subject = Subject::query()->create([
            'department_id' => $department->id,
            'name' => 'Mathematics',
            'code' => 'MATH7',
            'is_active' => true,
        ]);
    }

    public function test_can_save_schedule_for_multiple_days_at_once(): void
    {
        $daySlots = [];

        foreach (DayOfWeek::cases() as $day) {
            $daySlots[$day->value] = [
                'enabled' => in_array($day, [DayOfWeek::Monday, DayOfWeek::Wednesday, DayOfWeek::Friday], true),
                'starts_at' => '08:00',
                'ends_at' => '09:00',
            ];
        }

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('sectionId', $this->section->id)
            ->set('subjectId', $this->subject->id)
            ->set('teacherId', $this->teacher->id)
            ->set('semester', Semester::First->value)
            ->set('daySlots', $daySlots)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('class_schedules', 3);
        $this->assertDatabaseHas('class_schedules', [
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => DayOfWeek::Monday->value,
        ]);
        $this->assertDatabaseHas('class_schedules', [
            'section_id' => $this->section->id,
            'day_of_week' => DayOfWeek::Friday->value,
        ]);
    }

    public function test_select_weekdays_enables_monday_through_friday(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('defaultStartsAt', '10:00')
            ->set('defaultEndsAt', '11:00')
            ->call('selectWeekdays')
            ->assertSet('daySlots.1.enabled', true)
            ->assertSet('daySlots.5.enabled', true)
            ->assertSet('daySlots.6.enabled', false)
            ->assertSet('daySlots.1.starts_at', '10:00')
            ->assertSet('daySlots.1.ends_at', '11:00');
    }

    public function test_save_requires_at_least_one_day(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('sectionId', $this->section->id)
            ->set('subjectId', $this->subject->id)
            ->set('teacherId', $this->teacher->id)
            ->set('semester', Semester::First->value)
            ->call('save')
            ->assertHasErrors(['daySlots']);
    }

    public function test_update_or_create_replaces_existing_day_entry(): void
    {
        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Tuesday,
            'starts_at' => '07:00:00',
            'ends_at' => '08:00:00',
        ]);

        $daySlots = [];

        foreach (DayOfWeek::cases() as $day) {
            $daySlots[$day->value] = [
                'enabled' => $day === DayOfWeek::Tuesday,
                'starts_at' => '09:30',
                'ends_at' => '10:30',
            ];
        }

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('sectionId', $this->section->id)
            ->set('subjectId', $this->subject->id)
            ->set('teacherId', $this->teacher->id)
            ->set('semester', Semester::First->value)
            ->set('daySlots', $daySlots)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('class_schedules', 1);

        $schedule = ClassSchedule::query()->first();

        $this->assertSame(DayOfWeek::Tuesday->value, $schedule->day_of_week->value);
        $this->assertStringStartsWith('09:30', (string) $schedule->starts_at);
        $this->assertStringStartsWith('10:30', (string) $schedule->ends_at);
    }

    public function test_save_blocks_section_time_conflict(): void
    {
        $otherSubject = Subject::query()->create([
            'department_id' => $this->subject->department_id,
            'name' => 'Science',
            'code' => 'SCI7',
            'is_active' => true,
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Sunday,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        $daySlots = [];

        foreach (DayOfWeek::cases() as $day) {
            $daySlots[$day->value] = [
                'enabled' => $day === DayOfWeek::Sunday,
                'starts_at' => '08:00',
                'ends_at' => '09:00',
            ];
        }

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('sectionId', $this->section->id)
            ->set('subjectId', $this->subject->id)
            ->set('teacherId', $this->teacher->id)
            ->set('semester', Semester::First->value)
            ->set('daySlots', $daySlots)
            ->call('save')
            ->assertHasErrors(['conflicts']);

        $this->assertDatabaseCount('class_schedules', 1);
    }

    public function test_save_allows_non_overlapping_times_on_same_day(): void
    {
        $otherSubject = Subject::query()->create([
            'department_id' => $this->subject->department_id,
            'name' => 'Science',
            'code' => 'SCI7',
            'is_active' => true,
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $otherSubject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Sunday,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        $daySlots = [];

        foreach (DayOfWeek::cases() as $day) {
            $daySlots[$day->value] = [
                'enabled' => $day === DayOfWeek::Sunday,
                'starts_at' => '09:00',
                'ends_at' => '10:00',
            ];
        }

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('sectionId', $this->section->id)
            ->set('subjectId', $this->subject->id)
            ->set('teacherId', $this->teacher->id)
            ->set('semester', Semester::First->value)
            ->set('daySlots', $daySlots)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('class_schedules', 2);
    }

    public function test_conflict_service_detects_teacher_overlap(): void
    {
        $otherTeacher = User::factory()->create(['is_active' => true]);
        $otherTeacher->assignRole(UserRole::Teacher->value);

        $otherSection = Section::factory()->create([
            'grade_level_id' => $this->section->grade_level_id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'B-conflict',
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $otherSection->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Monday,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        $conflicts = app(ClassScheduleConflictService::class)->findConflictsForSlot(
            $this->academicYear->id,
            $this->section->id,
            $this->subject->id,
            $this->teacher->id,
            null,
            Semester::First->value,
            DayOfWeek::Monday->value,
            '08:00',
            '09:00',
        );

        $this->assertNotEmpty($conflicts);
        $this->assertSame('teacher', $conflicts[0]['type']);
    }

    public function test_reset_filters_clears_department_and_grade(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('department', '1')
            ->set('grade', '1')
            ->call('resetFilters')
            ->assertSet('department', '')
            ->assertSet('grade', '');
    }

    public function test_toggle_day_enables_and_disables_day_slot(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->call('toggleDay', DayOfWeek::Monday->value)
            ->assertSet('daySlots.1.enabled', true)
            ->call('toggleDay', DayOfWeek::Monday->value)
            ->assertSet('daySlots.1.enabled', false);
    }

    public function test_add_class_for_day_prepares_form_for_that_day(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->call('addClassForDay', DayOfWeek::Wednesday->value)
            ->assertSet('editingId', null)
            ->assertSet('daySlots.3.enabled', true)
            ->assertSet('daySlots.1.enabled', false);
    }

    public function test_schedule_overview_renders_stats_and_daily_sections(): void
    {
        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Monday,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Tuesday,
            'starts_at' => '08:00:00',
            'ends_at' => '09:00:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('semester', Semester::First->value)
            ->assertSee('Schedule Overview')
            ->assertSee('Total Classes')
            ->assertSee('Weekly Hours')
            ->assertSee('2')
            ->assertSee('Monday')
            ->assertSee('Tuesday')
            ->assertSee('Add Class');
    }

    public function test_copy_last_schedule_populates_form_from_latest_entry(): void
    {
        ClassSchedule::query()->create([
            'academic_year_id' => $this->academicYear->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'semester' => Semester::First,
            'day_of_week' => DayOfWeek::Thursday,
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('academicYearId', $this->academicYear->id)
            ->set('semester', Semester::First->value)
            ->call('copyLastSchedule')
            ->assertSet('sectionId', $this->section->id)
            ->assertSet('subjectId', $this->subject->id)
            ->assertSet('teacherId', $this->teacher->id)
            ->assertSet('defaultStartsAt', '10:00')
            ->assertSet('defaultEndsAt', '11:00')
            ->assertSet('daySlots.4.enabled', true);
    }
}
