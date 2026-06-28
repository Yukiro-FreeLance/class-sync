<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Administrator = 'administrator';
    case Registrar = 'registrar';
    case Teacher = 'teacher';
    case Guidance = 'guidance';
    case Accounting = 'accounting';
    case Cashier = 'cashier';
    case Principal = 'principal';
    case Clinic = 'clinic';
    case Security = 'security';
    case Student = 'student';
    case Parent = 'parent';

    public static function superAdminValue(): string
    {
        return (string) config('classsync.roles.super_admin', self::SuperAdmin->value);
    }

    public static function administratorValue(): string
    {
        return (string) config('classsync.roles.administrator', self::Administrator->value);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Administrator => 'Administrator',
            self::Registrar => 'Registrar',
            self::Teacher => 'Teacher',
            self::Guidance => 'Guidance',
            self::Accounting => 'Accounting',
            self::Cashier => 'Cashier',
            self::Principal => 'Principal',
            self::Clinic => 'Clinic',
            self::Security => 'Security',
            self::Student => 'Student',
            self::Parent => 'Parent',
        };
    }

    /**
     * Roles that bypass permission checks entirely.
     *
     * @return list<self>
     */
    public static function unrestricted(): array
    {
        return [
            self::SuperAdmin,
            self::Administrator,
        ];
    }

    /**
     * Roles whose permissions cannot be restricted and cannot be disabled.
     *
     * @return list<self>
     */
    public static function protected(): array
    {
        return [
            self::SuperAdmin,
            self::Administrator,
        ];
    }

    public function isUnrestricted(): bool
    {
        return in_array($this, self::unrestricted(), true);
    }

    public function isProtected(): bool
    {
        return in_array($this, self::protected(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }

    /**
     * Roles that can be assigned to staff accounts in admin settings.
     *
     * @return list<self>
     */
    public static function assignableStaff(): array
    {
        return [
            self::Administrator,
            self::Registrar,
            self::Teacher,
            self::Guidance,
            self::Accounting,
            self::Cashier,
            self::Principal,
            self::Clinic,
            self::Security,
        ];
    }

    /**
     * @return list<self>
     */
    public static function assignableFor(bool $canAssignSuperAdmin): array
    {
        $roles = self::assignableStaff();

        if ($canAssignSuperAdmin) {
            array_unshift($roles, self::SuperAdmin);
        }

        return $roles;
    }

    /**
     * Roles whose permission restrictions can be configured.
     *
     * @return list<self>
     */
    public static function configurable(): array
    {
        return self::assignableStaff();
    }
}
