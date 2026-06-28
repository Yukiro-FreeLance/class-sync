<?php

namespace App\Policies;

use App\Models\User;
use App\Services\Users\SuperAdminService;

class UserPolicy
{
    public function __construct(
        protected SuperAdminService $superAdmin,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->canManageUser($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->canManageUsers($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->canManageUser($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && $this->canManageUser($user, $model)
            && $user->can('users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        return $this->canManageUser($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && $this->canManageUser($user, $model)
            && $user->can('users.delete');
    }

    public function manageRoles(User $user): bool
    {
        return $this->superAdmin->canManageRoles($user);
    }

    protected function canManageUser(User $user, User $model): bool
    {
        if (! $this->superAdmin->canManageUser($user, $model)) {
            return false;
        }

        return $this->canManageUsers($user);
    }

    protected function canManageUsers(User $user): bool
    {
        if ($user->hasUnrestrictedAccess()) {
            return true;
        }

        return $user->can('users.view') || $user->can('users.update');
    }
}
