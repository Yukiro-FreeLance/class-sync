<?php

namespace App\Services\Users;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UserAccessService
{
    public function __construct(
        protected SuperAdminService $superAdmin,
    ) {}
    /**
     * @return array<string, list<string>>
     */
    public function permissionGroups(): array
    {
        return [
            'Students' => [
                'students.view',
                'students.create',
                'students.update',
                'students.archive',
                'students.restore',
                'students.delete',
            ],
            'Attendance' => [
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'attendance.delete',
            ],
            'Reports' => [
                'reports.view',
                'reports.create',
                'reports.export',
            ],
            'Settings' => [
                'settings.view',
                'settings.update',
            ],
            'Users & Access' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
            ],
            'Backups' => [
                'backups.view',
                'backups.create',
                'backups.delete',
                'backups.restore',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function allPermissions(): array
    {
        return collect($this->permissionGroups())->flatten()->values()->all();
    }

    public function permissionLabel(string $permission): string
    {
        return match ($permission) {
            'students.view' => 'View students',
            'students.create' => 'Add students',
            'students.update' => 'Edit students',
            'students.archive' => 'Archive students',
            'students.restore' => 'Restore archived students',
            'students.delete' => 'Permanently delete students',
            'attendance.view' => 'View attendance',
            'attendance.create' => 'Record attendance',
            'attendance.update' => 'Edit attendance',
            'attendance.delete' => 'Delete attendance records',
            'reports.view' => 'View reports',
            'reports.create' => 'Create reports',
            'reports.export' => 'Export reports',
            'settings.view' => 'View settings',
            'settings.update' => 'Update settings',
            'users.view' => 'View users',
            'users.create' => 'Add users',
            'users.update' => 'Edit users',
            'users.delete' => 'Delete users',
            'backups.view' => 'View backups',
            'backups.create' => 'Create backups',
            'backups.delete' => 'Delete backups',
            'backups.restore' => 'Restore backups',
            default => str_replace('.', ' · ', $permission),
        };
    }

    /**
     * @return Collection<int, Role>
     */
    public function manageableRoles(): Collection
    {
        $roleNames = collect(UserRole::configurable())
            ->merge(UserRole::unrestricted())
            ->unique()
            ->map->value
            ->all();

        return Role::query()
            ->whereIn('name', $roleNames)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function configurableRoles(): Collection
    {
        return Role::query()
            ->whereIn('name', collect(UserRole::configurable())->map->value->all())
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<UserRole>
     */
    public function assignableRolesFor(?User $assigner): array
    {
        $enabledRoleNames = Role::query()
            ->where('is_enabled', true)
            ->pluck('name')
            ->all();

        return collect(UserRole::assignableFor($assigner?->canAssignSuperAdminRole() ?? false))
            ->filter(fn (UserRole $role) => in_array($role->value, $enabledRoleNames, true))
            ->values()
            ->all();
    }

    public function isProtectedRole(string $roleName): bool
    {
        $role = UserRole::tryFrom($roleName);

        return $role?->isProtected() ?? false;
    }

    public function isUnrestrictedRole(string $roleName): bool
    {
        $role = UserRole::tryFrom($roleName);

        return $role?->isUnrestricted() ?? false;
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncRolePermissions(string $roleName, array $permissions): Role
    {
        if ($this->isUnrestrictedRole($roleName)) {
            $permissions = $this->allPermissions();
        }

        $role = Role::findByName($roleName, 'web');
        $role->syncPermissions(
            Permission::query()->whereIn('name', $permissions)->where('guard_name', 'web')->get(),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    public function setRoleEnabled(string $roleName, bool $enabled): Role
    {
        if ($this->isProtectedRole($roleName)) {
            throw new InvalidArgumentException('This role cannot be disabled.');
        }

        $role = Role::findByName($roleName, 'web');
        $role->update(['is_enabled' => $enabled]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $roles
     */
    public function createUser(array $data, array $roles): User
    {
        return DB::transaction(function () use ($data, $roles) {
            $user = User::query()->create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => Str::lower($data['username']),
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'acts_as_teacher' => $data['acts_as_teacher'] ?? false,
                'email_verified_at' => now(),
            ]);

            $this->syncUserRoles($user, $roles, (bool) ($data['acts_as_teacher'] ?? false));

            return $user->fresh(['roles']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $roles
     */
    public function updateUser(User $user, array $data, array $roles): User
    {
        return DB::transaction(function () use ($user, $data, $roles) {
            $payload = [
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => Str::lower($data['username']),
                'email' => Str::lower($data['email']),
                'is_active' => $data['is_active'] ?? $user->is_active,
                'acts_as_teacher' => $data['acts_as_teacher'] ?? false,
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);
            $this->syncUserRoles($user, $roles, (bool) ($data['acts_as_teacher'] ?? false));

            return $user->fresh(['roles']);
        });
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncUserRoles(User $user, array $roles, bool $actsAsTeacher): void
    {
        $roles = collect($roles)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($actsAsTeacher && ! in_array(UserRole::Teacher->value, $roles, true)) {
            $roles[] = UserRole::Teacher->value;
        }

        $user->syncRoles($roles);
        $user->update(['acts_as_teacher' => $actsAsTeacher]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function administratorCount(): int
    {
        return User::role(UserRole::Administrator->value)->count();
    }

    public function superAdminCount(): int
    {
        return User::role($this->superAdmin->roleName())->count();
    }

    public function privilegedAccountCount(): int
    {
        return User::role($this->superAdmin->privilegedRoleNames())->count();
    }

    /**
     * @param  list<string>  $roles
     */
    public function wouldLeaveNoPrivilegedAccount(User $user, array $roles): bool
    {
        $privilegedRoles = $this->superAdmin->privilegedRoleNames();

        $hasPrivilegedRole = fn (array $roleNames) => collect($roleNames)->contains(
            fn (string $role) => in_array($role, $privilegedRoles, true),
        );

        if ($hasPrivilegedRole($roles)) {
            return false;
        }

        if (! $user->hasAnyRole($privilegedRoles)) {
            return false;
        }

        return User::query()
            ->where('id', '!=', $user->id)
            ->role($privilegedRoles)
            ->count() === 0;
    }
}
