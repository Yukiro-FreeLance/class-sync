<?php

namespace Tests\Feature;

use App\Enums\Semester;
use App\Enums\UserRole;
use App\Livewire\Settings\Academic\Schedules;
use App\Livewire\Settings\Academic\Structure;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentSemesterTest extends TestCase
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

    public function test_department_defaults_to_first_and_second_semester(): void
    {
        $department = Department::query()->create([
            'name' => 'Test Elementary',
            'code' => 'elem-test',
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $this->assertSame(['first', 'second'], $department->configuredSemesters());
        $this->assertTrue($department->allowsSemester(Semester::First));
        $this->assertFalse($department->allowsSemester(Semester::Summer));
    }

    public function test_new_department_gets_default_semesters_on_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Structure::class)
            ->set('departmentName', 'New Department')
            ->set('departmentCode', 'new-dept')
            ->set('departmentSortOrder', 1)
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $department = Department::query()->where('code', 'new-dept')->first();

        $this->assertNotNull($department);
        $this->assertSame(['first', 'second'], $department->configuredSemesters());
    }

    public function test_admin_can_configure_department_semesters(): void
    {
        $department = Department::query()->create([
            'name' => 'Senior High School',
            'code' => 'shs-sem-test',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Structure::class)
            ->call('configureSemesters', $department->id)
            ->set('departmentSemesterConfig', [
                'first' => ['enabled' => true, 'label' => 'First Semester'],
                'second' => ['enabled' => true, 'label' => 'Second Semester'],
                'summer' => ['enabled' => true, 'label' => 'Summer Term'],
            ])
            ->call('saveSemesters')
            ->assertHasNoErrors();

        $department->refresh();

        $this->assertSame(['first', 'second', 'summer'], $department->configuredSemesters());
        $this->assertSame(
            ['first' => 'First Semester', 'second' => 'Second Semester', 'summer' => 'Summer Term'],
            $department->semesterOptions(),
        );
    }

    public function test_admin_can_edit_custom_semester_labels(): void
    {
        $department = Department::query()->create([
            'name' => 'Junior High School',
            'code' => 'jhs-label-test',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Structure::class)
            ->call('configureSemesters', $department->id)
            ->set('departmentSemesterConfig', [
                'first' => ['enabled' => true, 'label' => '1st Quarter'],
                'second' => ['enabled' => true, 'label' => '2nd Quarter'],
                'summer' => ['enabled' => false, 'label' => 'Summer'],
            ])
            ->call('saveSemesters')
            ->assertHasNoErrors();

        $department->refresh();

        $this->assertSame(['first' => '1st Quarter', 'second' => '2nd Quarter'], $department->semesterOptions());
        $this->assertSame('1st Quarter', $department->labelForSemester('first'));
    }

    public function test_schedules_filter_semesters_by_department(): void
    {
        $elem = Department::query()->create([
            'name' => 'Test Elementary',
            'code' => 'elem-filter',
            'sort_order' => 98,
            'is_active' => true,
            'semesters' => ['first', 'second'],
        ]);

        $shs = Department::query()->create([
            'name' => 'Test Senior High',
            'code' => 'shs-filter',
            'sort_order' => 99,
            'is_active' => true,
            'semesters' => ['first', 'second', 'summer'],
        ]);

        Livewire::actingAs($this->admin)
            ->test(Schedules::class)
            ->set('department', (string) $elem->id)
            ->assertViewHas('semesters', ['first' => 'First Semester', 'second' => 'Second Semester'])
            ->set('department', (string) $shs->id)
            ->assertViewHas('semesters', [
                'first' => 'First Semester',
                'second' => 'Second Semester',
                'summer' => 'Summer',
            ]);
    }

    public function test_save_semesters_requires_at_least_one_enabled(): void
    {
        $department = Department::query()->create([
            'name' => 'Test Dept',
            'code' => 'req-test',
            'sort_order' => 9,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Structure::class)
            ->call('configureSemesters', $department->id)
            ->set('departmentSemesterConfig', [
                'first' => ['enabled' => false, 'label' => 'First Semester'],
                'second' => ['enabled' => false, 'label' => 'Second Semester'],
                'summer' => ['enabled' => false, 'label' => 'Summer'],
            ])
            ->call('saveSemesters')
            ->assertHasErrors(['departmentSemesterConfig']);
    }
}
