<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\Index;
use App\Livewire\Students\Show;
use App\Models\Student;
use App\Models\User;
use App\Services\Users\UserAccessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole(UserRole::Administrator->value);

        $this->registrar = User::factory()->create(['is_active' => true]);
        $this->registrar->assignRole(UserRole::Registrar->value);
    }

    public function test_admin_can_archive_student(): void
    {
        $student = Student::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['student' => $student])
            ->call('archive')
            ->assertRedirect(route('students.index'));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_admin_can_restore_archived_student(): void
    {
        $student = Student::factory()->create();
        $student->delete();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['student' => $student->fresh()])
            ->call('restore')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'deleted_at' => null,
            'status' => 'active',
        ]);
    }

    public function test_admin_cannot_permanently_delete_active_student(): void
    {
        $student = Student::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['student' => $student])
            ->call('forceDelete')
            ->assertForbidden();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    }

    public function test_admin_can_permanently_delete_archived_student(): void
    {
        $student = Student::factory()->create();
        $student->delete();

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['student' => $student->fresh()])
            ->call('forceDelete')
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_lifecycle_actions_depend_on_archive_state(): void
    {
        $student = Student::factory()->create();

        $this->assertTrue($this->admin->can('archive', $student));
        $this->assertFalse($this->admin->can('restore', $student));
        $this->assertFalse($this->admin->can('delete', $student));

        $student->delete();

        $this->assertFalse($this->admin->can('archive', $student->fresh()));
        $this->assertTrue($this->admin->can('restore', $student->fresh()));
        $this->assertTrue($this->admin->can('delete', $student->fresh()));
    }

    public function test_registrar_cannot_archive_student_by_default(): void
    {
        $student = Student::factory()->create();

        $this->assertFalse($this->registrar->can('archive', $student));
        $this->assertFalse($this->registrar->can('delete', $student));

        Livewire::actingAs($this->registrar)
            ->test(Show::class, ['student' => $student])
            ->call('archive')
            ->assertForbidden();
    }

    public function test_role_can_be_granted_archive_permission(): void
    {
        app(UserAccessService::class)->syncRolePermissions(UserRole::Registrar->value, [
            'students.view',
            'students.create',
            'students.update',
            'students.archive',
        ]);

        $this->registrar->unsetRelation('roles');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $student = Student::factory()->create();

        $this->assertTrue($this->registrar->fresh()->can('archive', $student));

        Livewire::actingAs($this->registrar)
            ->test(Index::class)
            ->call('archive', $student->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_archived_students_hidden_from_default_list(): void
    {
        $active = Student::factory()->create(['last_name' => 'Active']);
        $archived = Student::factory()->create(['last_name' => 'Archived']);
        $archived->delete();

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->assertSee($active->list_name)
            ->assertDontSee($archived->list_name)
            ->set('showArchived', true)
            ->assertSee($archived->list_name);
    }

    public function test_archived_student_cannot_be_edited_by_non_admin(): void
    {
        $student = Student::factory()->create();
        $student->delete();

        $this->assertFalse($this->registrar->can('update', $student->fresh()));

        $this->actingAs($this->registrar)
            ->get(route('students.edit', $student))
            ->assertNotFound();
    }
}
