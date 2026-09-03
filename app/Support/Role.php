<?php

namespace App\Support;

class Role
{
    public const SUPER_ADMIN = 'super_admin';

    public const HR = 'hr';

    public const FINANCE = 'finance';

    public const MANAGEMENT = 'management';

    public const SCHOOL_ADMIN = 'school_admin';

    public const ALL = [
        self::SUPER_ADMIN,
        self::HR,
        self::FINANCE,
        self::MANAGEMENT,
        self::SCHOOL_ADMIN,
    ];

    public const LABELS = [
        self::SUPER_ADMIN => 'Super Admin',
        self::HR => 'HR / Admin',
        self::FINANCE => 'Accounts / Payroll',
        self::MANAGEMENT => 'Management',
        self::SCHOOL_ADMIN => 'School Admin',
    ];

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }
}
