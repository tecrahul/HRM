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

    /**
     * Get the privilege level of this role (higher = more privileged).
     * Used for role hierarchy comparisons.
     */
    public function level(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 5,
            self::ADMIN => 4,
            self::HR => 3,
            self::FINANCE => 3,
            self::EMPLOYEE => 1,
        };
    }

    /**
     * Check if this role has higher privilege than another role.
     */
    public function isHigherThan(self|string $otherRole): bool
    {
        if (is_string($otherRole)) {
            $otherRole = self::tryFrom($otherRole);
            if ($otherRole === null) {
                return false;
            }
        }

        return $this->level() > $otherRole->level();
    }
}
