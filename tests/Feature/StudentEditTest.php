<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\Edit;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StudentEditTest extends TestCase
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

    public function test_admin_can_view_edit_page(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('students.edit', $student))
            ->assertOk();
    }

    public function test_admin_can_update_student_info(): void
    {
        $student = Student::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Edit::class, ['student' => $student])
            ->set('first_name', 'Maria')
            ->set('last_name', 'Santos')
            ->set('address', '123 Main St')
            ->call('save')
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'address' => '123 Main St',
        ]);
    }

    public function test_admin_can_update_student_when_rfid_tag_is_blank_and_another_student_has_blank_rfid(): void
    {
        $first = Student::factory()->create(['rfid_tag' => null]);
        DB::table('students')->where('id', $first->id)->update(['rfid_tag' => '']);

        $second = Student::factory()->create(['rfid_tag' => null]);

        Livewire::actingAs($this->admin)
            ->test(Edit::class, ['student' => $second])
            ->set('first_name', 'Updated')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('students.show', $second));

        $this->assertDatabaseHas('students', [
            'id' => $second->id,
            'first_name' => 'Updated',
            'rfid_tag' => null,
        ]);
    }
}
