<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $permissions = [
        'students.view',
        'students.create',
        'students.update',
        'students.archive',
        'students.restore',
        'students.delete',
        'attendance.view',
        'attendance.create',
        'attendance.update',
        'attendance.delete',
        'reports.view',
        'reports.create',
        'reports.export',
        'settings.view',
        'settings.update',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'backups.view',
        'backups.create',
        'backups.delete',
        'backups.restore',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(
                [
                    'name' => $role->value,
                    'guard_name' => 'web',
                ],
                [
                    'is_enabled' => true,
                ],
            );
        }

        $allPermissions = Permission::whereIn('name', $this->permissions)->where('guard_name', 'web')->get();

        foreach (UserRole::unrestricted() as $role) {
            Role::findByName($role->value, 'web')->syncPermissions($allPermissions);
        }

        $this->seedDefaultRolePermissions();
    }

    protected function seedDefaultRolePermissions(): void
    {
        $map = [
            UserRole::Teacher->value => [
                'students.view',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'reports.view',
            ],
            UserRole::Registrar->value => [
                'students.view',
                'students.create',
                'students.update',
                'attendance.view',
                'reports.view',
                'reports.export',
                'settings.view',
            ],
            UserRole::Principal->value => [
                'students.view',
                'students.update',
                'attendance.view',
                'attendance.create',
                'attendance.update',
                'reports.view',
                'reports.export',
                'settings.view',
            ],
            UserRole::Guidance->value => [
                'students.view',
                'students.update',
                'reports.view',
            ],
            UserRole::Security->value => [
                'students.view',
                'attendance.view',
                'attendance.create',
            ],
            UserRole::Accounting->value => [
                'students.view',
                'reports.view',
            ],
            UserRole::Cashier->value => [
                'students.view',
            ],
            UserRole::Clinic->value => [
                'students.view',
                'students.update',
            ],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');
            $role->syncPermissions(
                Permission::whereIn('name', $permissions)->where('guard_name', 'web')->get()
            );
        }
    }
}
