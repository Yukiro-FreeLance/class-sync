<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SuperAdminService
{
    public function roleName(): string
    {
        return (string) config('classsync.roles.super_admin', UserRole::SuperAdmin->value);
    }

    public function setupRoleName(): string
    {
        return (string) config('classsync.roles.setup_default_role', UserRole::SuperAdmin->value);
    }

    public function manageRolesAbility(): string
    {
        return (string) config('classsync.roles.manage_roles_ability', 'manageRoles');
    }

    public function is(User $user): bool
    {
        return $user->hasRole($this->roleName());
    }

    public function bypassesAllAuthorization(User $user): bool
    {
        return $this->is($user);
    }

    public function canManageRoles(User $user): bool
    {
        return $this->is($user);
    }

    public function canAssignRole(User $assigner): bool
    {
        return $this->is($assigner);
    }

    public function canViewUser(User $viewer, User $target): bool
    {
        if ($this->is($target) && ! $this->is($viewer)) {
            return false;
        }

        return true;
    }

    public function canManageUser(User $viewer, User $target): bool
    {
        return $this->canViewUser($viewer, $target);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function applyUserVisibilityScope(Builder $query, User $viewer): Builder
    {
        if ($this->is($viewer)) {
            return $query;
        }

        return $query->whereDoesntHave('roles', fn (Builder $roleQuery) => $roleQuery->where(
            'name',
            $this->roleName(),
        ));
    }

    public function ensureCanManageUser(User $viewer, User $target): void
    {
        if (! $this->canManageUser($viewer, $target)) {
            throw new AccessDeniedHttpException('You cannot manage this account.');
        }
    }

    public function ensureCanManageRoles(User $user): void
    {
        if (! $this->canManageRoles($user)) {
            throw new AccessDeniedHttpException('Only Super Admins can manage roles and permissions.');
        }
    }

    /**
     * @return list<string>
     */
    public function privilegedRoleNames(): array
    {
        return [
            $this->roleName(),
            (string) config('classsync.roles.administrator', UserRole::Administrator->value),
        ];
    }
}
