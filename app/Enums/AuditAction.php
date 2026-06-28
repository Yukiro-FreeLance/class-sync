<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case View = 'view';
    case Export = 'export';
    case Import = 'import';
    case Backup = 'backup';
    case Restore = 'restore';
    case Attendance = 'attendance';
    case Settings = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::Create => 'Create',
            self::Update => 'Update',
            self::Delete => 'Delete',
            self::View => 'View',
            self::Export => 'Export',
            self::Import => 'Import',
            self::Backup => 'Backup',
            self::Restore => 'Restore',
            self::Attendance => 'Attendance',
            self::Settings => 'Settings',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action) => [$action->value => $action->label()])
            ->all();
    }
}
