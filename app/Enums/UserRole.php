<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case HR = 'hr';
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
            self::ADMIN => 'Admin',
            self::HR => 'HR',
            self::EMPLOYEE => 'Employee',
        };
    }
}
