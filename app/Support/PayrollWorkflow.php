<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Payroll;
use App\Models\User;

class PayrollWorkflow
{
    public static function canGenerate(User $viewer): bool
    {
        return $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::HR->value,
            UserRole::ADMIN->value,
        ]);
    }

    public static function canApprove(User $viewer): bool
    {
        return $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ]);
    }

    public static function canMarkPaid(User $viewer): bool
    {
        return $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN->value,
        ]);
    }

    public static function canUnlock(User $viewer): bool
    {
        return $viewer->hasRole(UserRole::SUPER_ADMIN->value);
    }

    public static function dbStatusToUiStatus(string $dbStatus): string
    {
        return match ($dbStatus) {
            Payroll::STATUS_DRAFT => 'generated',
            Payroll::STATUS_PROCESSED => 'approved',
            Payroll::STATUS_PAID => 'paid',
            Payroll::STATUS_FAILED => 'failed',
            default => 'generated',
        };
    }

    public static function uiStatusToDbStatus(?string $uiStatus): ?string
    {
        $status = strtolower((string) ($uiStatus ?? ''));

        return match ($status) {
            'generated' => Payroll::STATUS_DRAFT,
            'approved' => Payroll::STATUS_PROCESSED,
            'paid' => Payroll::STATUS_PAID,
            'failed' => Payroll::STATUS_FAILED,
            default => null,
        };
    }

    public static function statusLabelFromDb(string $dbStatus): string
    {
        return match ($dbStatus) {
            Payroll::STATUS_DRAFT => 'Generated',
            Payroll::STATUS_PROCESSED => 'Approved',
            Payroll::STATUS_PAID => 'Paid',
            Payroll::STATUS_FAILED => 'Failed',
            default => (string) str($dbStatus)->replace('_', ' ')->title(),
        };
    }
}
