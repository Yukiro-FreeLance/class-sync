<?php

namespace App\Livewire\Settings\Users;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserAccessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Roles & Restrictions')]
class Roles extends Component
{
    #[Url]
    public string $role = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public function mount(UserAccessService $accessService): void
    {
        $this->authorize('manageRoles', User::class);

        $this->role = $this->role ?: UserRole::Teacher->value;
        $this->loadRolePermissions();
    }

    public function updatedRole(): void
    {
        $this->loadRolePermissions();
    }

    public function loadRolePermissions(): void
    {
        $role = Role::findByName($this->role, 'web');
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
    }

    public function save(UserAccessService $accessService): void
    {
        $this->authorize('manageRoles', User::class);

        if ($accessService->isUnrestrictedRole($this->role)) {
            $this->dispatch('toast', message: 'This role always has full access.', type: 'info');

            return;
        }

        $valid = collect($accessService->allPermissions());
        $permissions = collect($this->selectedPermissions)
            ->filter(fn ($permission) => $valid->contains($permission))
            ->values()
            ->all();

        $accessService->syncRolePermissions($this->role, $permissions);

        $this->dispatch('toast', message: 'Role restrictions updated.', type: 'success');
        $this->loadRolePermissions();
    }

    public function toggleEnabled(UserAccessService $accessService): void
    {
        $this->authorize('manageRoles', User::class);

        if ($accessService->isProtectedRole($this->role)) {
            $this->dispatch('toast', message: 'This role cannot be disabled.', type: 'warning');

            return;
        }

        $roleModel = Role::findByName($this->role, 'web');
        $accessService->setRoleEnabled($this->role, ! $roleModel->is_enabled);

        $this->dispatch(
            'toast',
            message: $roleModel->fresh()->is_enabled ? 'Role enabled.' : 'Role disabled.',
            type: 'success',
        );
    }

    public function render(UserAccessService $accessService)
    {
        $roleModel = Role::findByName($this->role, 'web');

        return view('livewire.settings.users.roles', [
            'roles' => $accessService->manageableRoles(),
            'permissionGroups' => $accessService->permissionGroups(),
            'permissionLabels' => fn (string $permission) => $accessService->permissionLabel($permission),
            'isUnrestrictedRole' => $accessService->isUnrestrictedRole($this->role),
            'isProtectedRole' => $accessService->isProtectedRole($this->role),
            'roleEnabled' => $roleModel->is_enabled,
        ]);
    }
}
