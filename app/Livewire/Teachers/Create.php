<?php

namespace App\Livewire\Teachers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Add Teacher')]
class Create extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'is_active' => ['boolean'],
        ]);

        $teacher = User::query()->create([
            'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => Str::lower($validated['username']),
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
            'is_active' => $validated['is_active'],
            'email_verified_at' => now(),
        ]);

        $teacher->assignRole(UserRole::Teacher->value);

        $this->dispatch('toast', message: 'Teacher account created successfully.', type: 'success');
        $this->redirect(route('teachers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.teachers.create');
    }
}
