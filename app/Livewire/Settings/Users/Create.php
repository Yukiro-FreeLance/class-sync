<?php

namespace App\Livewire\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Users\SuperAdminService;
use App\Services\Users\UserAccessService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Add User')]
class Create extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public bool $acts_as_teacher = false;

    /** @var array<int, string> */
    public array $selectedRoles = [];

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function updatedSelectedRoles(): void
    {
        if (! $this->hasUnrestrictedRoleSelected()) {
            $this->acts_as_teacher = false;
        }
    }

    protected function hasUnrestrictedRoleSelected(): bool
    {
        $superAdminRole = app(SuperAdminService::class)->roleName();

        return collect($this->selectedRoles)->contains(fn (string $role) => in_array($role, [
            $superAdminRole,
            UserRole::Administrator->value,
        ], true));
    }

    public function save(UserAccessService $accessService): void
    {
        $this->authorize('create', User::class);

        $assignableRoleValues = collect($accessService->assignableRolesFor(auth()->user()))
            ->map->value
            ->all();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'is_active' => ['boolean'],
            'acts_as_teacher' => ['boolean'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::in($assignableRoleValues)],
        ]);

        $accessService->createUser($validated, $this->selectedRoles);

        $this->dispatch('toast', message: 'User created successfully.', type: 'success');
        $this->redirect(route('settings.users.index'), navigate: true);
    }

    public function render(UserAccessService $accessService)
    {
        return view('livewire.settings.users.create', [
            'assignableRoles' => $accessService->assignableRolesFor(auth()->user()),
        ]);
    }
}
