<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any payroll records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'payroll.view',
            'payroll.view.all',
        ]);
    }

    /**
     * Determine if the user can view a specific payroll record.
     * Uses PermissionScopeResolver for efficient scope checking.
     */
    public function view(User $user, Payroll $payroll): bool
    {
        // Use PermissionScopeResolver to determine scope
        $scope = PermissionScopeResolver::resolve($user, 'payroll.view');

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'self') {
            return (int) $payroll->user_id === (int) $user->id;
        }

        // No permission
        return false;
    }

    /**
     * Determine if the user can generate payroll.
     */
    public function generate(User $user): bool
    {
        return $user->hasPermission('payroll.generate');
    }

    /**
     * Determine if the user can approve payroll.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermission('payroll.approve');
    }

    /**
     * Determine if the user can mark payroll as paid.
     */
    public function pay(User $user): bool
    {
        return $user->hasPermission('payroll.pay');
    }

    /**
     * Determine if the user can edit payroll.
     */
    public function update(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.edit');
    }

    /**
     * Determine if the user can delete payroll.
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->hasPermission('payroll.delete');
    }

    /**
     * Determine if the user can manage salary structures.
     */
    public function manageStructure(User $user): bool
    {
        return $user->hasPermission('payroll.manage_structure');
    }

    /**
     * Determine if the user can export payroll data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('payroll.export');
    }
}
