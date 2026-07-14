<?php

namespace App\Services\Navigation;

use App\Enums\UserRole;
use App\Models\User;

class SidebarNavigationService
{
    /**
     * @return list<array{key: string, label: string, route: ?string, icon: string, disabled?: bool, permission?: string|list<string>}>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            ],
            [
                'key' => 'students',
                'label' => 'Students',
                'route' => 'students.index',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'permission' => 'students.view',
            ],
            [
                'key' => 'teachers',
                'label' => 'Teachers',
                'route' => 'teachers.index',
                'icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342',
                'permission' => 'users.view',
            ],
            [
                'key' => 'attendance',
                'label' => 'Attendance',
                'route' => 'attendance.bulk',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'permission' => 'attendance.view',
            ],
            // [
            //     'key' => 'attendance-monitor',
            //     'label' => 'Live Monitor',
            //     'route' => 'attendance.monitor',
            //     'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            //     'permission' => 'attendance.view',
            // ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'route' => 'reports.index',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'permission' => 'reports.view',
            ],
            // [
            //     'key' => 'visitors',
            //     'label' => 'Visitors',
            //     'route' => null,
            //     'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            //     'disabled' => true,
            //     'permission' => 'settings.update',
            // ],
            [
                'key' => 'users',
                'label' => 'Users & Access',
                'route' => 'settings.users.index',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'permission' => 'users.view',
            ],
            [
                'key' => 'academic',
                'label' => 'Academic Config',
                'route' => 'settings.academic.structure',
                'icon' => 'M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20',
                'permission' => 'settings.update',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'route' => 'settings.general',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'permission' => 'settings.view',
            ],
            [
                'key' => 'audit-logs',
                'label' => 'Audit Logs',
                'route' => 'audit-logs.index',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'permission' => 'settings.update',
            ],
            // [
            //     'key' => 'backups',
            //     'label' => 'Backups',
            //     'route' => 'settings.backup',
            //     'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
            //     'permission' => 'backups.view',
            // ],
            [
                'key' => 'subscription',
                'label' => 'Subscription',
                'route' => 'settings.subscription',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'super_admin_only' => true,
            ],
            [
                'key' => 'application-package',
                'label' => 'Desktop App',
                'route' => 'settings.application-package',
                'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'super_admin_only' => true,
            ],
            [
                'key' => 'help',
                'label' => 'Help',
                'route' => 'help.index',
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, route: ?string, icon: string, disabled?: bool}>
     */
    public function itemsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect($this->definitions())
            ->filter(fn (array $item) => $this->canSee($user, $item))
            ->values()
            ->all();
    }

    public function isActive(array $item): bool
    {
        $route = $item['route'] ?? null;

        if (! $route) {
            return false;
        }

        return match ($item['key']) {
            'dashboard' => request()->routeIs('dashboard'),
            'students' => request()->routeIs('students.*'),
            'teachers' => request()->routeIs('teachers.*'),
            'attendance' => request()->routeIs('attendance.index', 'attendance.bulk', 'attendance.scanner'),
            'attendance-monitor' => request()->routeIs('attendance.monitor'),
            'reports' => request()->routeIs('reports.*'),
            'users' => request()->routeIs('settings.users.*'),
            'academic' => request()->routeIs('settings.academic.*'),
            'settings' => request()->routeIs('settings.general', 'settings.attendance'),
            'audit-logs' => request()->routeIs('audit-logs.*'),
            'backups' => request()->routeIs('settings.backup'),
            'subscription' => request()->routeIs('settings.subscription'),
            'application-package' => request()->routeIs(
                'settings.application-package',
                'settings.application-package.download',
                'settings.application-package.icon',
            ),
            'help' => request()->routeIs('help.*'),
            default => request()->routeIs($route),
        };
    }

    /**
     * @return list<string>
     */
    public function roleLabelsFor(User $user): array
    {
        return $user->roles
            ->pluck('name')
            ->map(fn (string $role) => UserRole::tryFrom($role)?->label() ?? ucfirst($role))
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    public function commandItemsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $commands = [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'permission' => null],
            ['label' => 'Students', 'href' => route('students.index'), 'permission' => 'students.view'],
            ['label' => 'Bulk Enrollment', 'href' => route('students.enrollment'), 'permission' => 'students.update'],
            ['label' => 'Master List', 'href' => route('students.lists.master'), 'permission' => 'students.view'],
            ['label' => 'Class List', 'href' => route('students.lists.class'), 'permission' => 'students.view'],
            ['label' => 'Add Student', 'href' => route('students.create'), 'permission' => 'students.create'],
            ['label' => 'Teachers', 'href' => route('teachers.index'), 'permission' => 'users.view'],
            ['label' => 'Attendance', 'href' => route('attendance.index'), 'permission' => 'attendance.view'],
            ['label' => 'Bulk Attendance', 'href' => route('attendance.bulk'), 'permission' => 'attendance.create'],
            ['label' => 'Scanner', 'href' => route('attendance.scanner'), 'permission' => 'attendance.create'],
            ['label' => 'Live Monitor', 'href' => route('attendance.monitor'), 'permission' => 'attendance.view'],
            ['label' => 'Reports', 'href' => route('reports.index'), 'permission' => 'reports.view'],
            ['label' => 'Users & Access', 'href' => route('settings.users.index'), 'permission' => 'users.view'],
            ['label' => 'Academic Config', 'href' => route('settings.academic.structure'), 'permission' => 'settings.update'],
            ['label' => 'Settings', 'href' => route('settings.general'), 'permission' => 'settings.view'],
            ['label' => 'Audit Logs', 'href' => route('audit-logs.index'), 'permission' => 'settings.update'],
            ['label' => 'Backups', 'href' => route('settings.backup'), 'permission' => 'backups.view'],
            ['label' => 'Subscription', 'href' => route('settings.subscription'), 'permission' => null, 'super_admin_only' => true],
            ['label' => 'Desktop App', 'href' => route('settings.application-package'), 'permission' => null, 'super_admin_only' => true],
            ['label' => 'Help & User Manual', 'href' => route('help.index'), 'permission' => null],
        ];

        return collect($commands)
            ->filter(function (array $command) use ($user) {
                if (! empty($command['super_admin_only']) && ! $user->isSuperAdmin()) {
                    return false;
                }

                return ($command['permission'] ?? null) === null || $user->can($command['permission']);
            })
            ->map(fn (array $command) => ['label' => $command['label'], 'href' => $command['href']])
            ->values()
            ->all();
    }

    protected function canSee(User $user, array $item): bool
    {
        if (! empty($item['super_admin_only']) && ! $user->isSuperAdmin()) {
            return false;
        }

        if (! empty($item['disabled'])) {
            return isset($item['permission']) ? $user->can($item['permission']) : false;
        }

        if (empty($item['permission'])) {
            return true;
        }

        if (is_array($item['permission'])) {
            return $user->canAny($item['permission']);
        }

        return $user->can($item['permission']);
    }
}
