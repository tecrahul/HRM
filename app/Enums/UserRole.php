<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case HR = 'hr';
    case FINANCE = 'finance';
    case EMPLOYEE = 'employee';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::HR => 'HR',
            self::FINANCE => 'Finance',
            self::EMPLOYEE => 'Employee',
        };
    }
}
