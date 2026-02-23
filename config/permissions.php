<?php

use App\Enums\UserRole;

return [
    'map' => [
        'attendance.view.self' => [
            UserRole::EMPLOYEE->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.view.department' => [
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.view.all' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.create' => [
            UserRole::EMPLOYEE->value,
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.edit' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.delete' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.approve' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.reject' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.lock.month' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.unlock.month' => [
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'attendance.export' => [
            UserRole::FINANCE->value,
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'holiday.view' => [
            UserRole::EMPLOYEE->value,
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'holiday.create' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'holiday.edit' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
        'holiday.delete' => [
            UserRole::HR->value,
            UserRole::ADMIN->value,
            UserRole::SUPER_ADMIN->value,
        ],
    ],
];
