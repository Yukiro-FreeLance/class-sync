<?php

namespace App\Livewire\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Users\SuperAdminService;
use App\Services\Users\UserAccessService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public User $user;

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

    public function mount(User $user, SuperAdminService $superAdmin): void
    {
        $superAdmin->ensureCanManageUser(auth()->user(), $user);

        $this->authorize('update', $user);

        $this->user = $user->load('roles');
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->is_active = $user->is_active;
        $this->acts_as_teacher = $user->acts_as_teacher;
        $this->selectedRoles = $user->roles->pluck('name')->all();
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
        $this->authorize('update', $this->user);

        $assignableRoleValues = collect($accessService->assignableRolesFor(auth()->user()))
            ->map->value
            ->all();

        $allowedRoleValues = collect($assignableRoleValues)
            ->merge($this->user->roles->pluck('name'))
            ->unique()
            ->values()
            ->all();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($this->user->id), 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'is_active' => ['boolean'],
            'acts_as_teacher' => ['boolean'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::in($allowedRoleValues)],
        ]);

        if ($accessService->wouldLeaveNoPrivilegedAccount($this->user, $this->selectedRoles)) {
            $this->addError('selectedRoles', 'At least one Super Admin or Administrator account is required.');

            return;
        }

        if ($this->user->id === auth()->id() && ! $this->is_active) {
            $this->addError('is_active', 'You cannot deactivate your own account.');

            return;
        }

        $accessService->updateUser($this->user, $validated, $this->selectedRoles);

        $this->dispatch('toast', message: 'User updated successfully.', type: 'success');
        $this->redirect(route('settings.users.index'), navigate: true);
    }

    public function render(UserAccessService $accessService)
    {
        return view('livewire.settings.users.edit', [
            'assignableRoles' => $accessService->assignableRolesFor(auth()->user()),
        ])->title('Edit '.$this->user->full_name);
    }
}
