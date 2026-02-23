<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const REQUIRED_ATTENDANCE_PERMISSIONS = [
        'attendance.view.self',
        'attendance.view.department',
        'attendance.view.all',
        'attendance.create',
        'attendance.edit',
        'attendance.delete',
        'attendance.approve',
        'attendance.reject',
        'attendance.lock.month',
        'attendance.unlock.month',
        'attendance.export',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_HOLIDAY_PERMISSIONS = [
        'holiday.view',
        'holiday.create',
        'holiday.edit',
        'holiday.delete',
    ];

    public function run(): void
    {
        $map = config('permissions.map', []);
        $requiredPermissions = array_merge(
            self::REQUIRED_ATTENDANCE_PERMISSIONS,
            self::REQUIRED_HOLIDAY_PERMISSIONS
        );

        $missing = array_values(array_filter(
            $requiredPermissions,
            static fn (string $permission): bool => ! array_key_exists($permission, $map)
        ));

        if ($missing !== []) {
            throw new RuntimeException('Missing required permissions: '.implode(', ', $missing));
        }
    }
}
