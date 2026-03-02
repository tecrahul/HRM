<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any leave requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'leave.view',
            'leave.view.department',
            'leave.view.all',
        ]);
    }

    /**
     * Determine if the user can view a specific leave request.
     * Uses PermissionScopeResolver for efficient scope checking.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // Use PermissionScopeResolver to determine scope
        $scope = PermissionScopeResolver::resolve($user, 'leave.view');

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'department') {
            // Check if viewer and leave requester are in the same department
            $viewerDepartment = (string) ($user->profile?->department ?? '');
            $leaveDepartment = (string) ($leaveRequest->user?->profile?->department ?? '');

            return $viewerDepartment !== '' && $viewerDepartment === $leaveDepartment;
        }

        if ($scope === 'self') {
            return (int) $leaveRequest->user_id === (int) $user->id;
        }

        // No permission
        return false;
    }

    /**
     * Determine if the user can create/apply for leave.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['leave.apply', 'leave.create']);
    }

    /**
     * Determine if the user can update/edit a leave request.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can edit if user has general edit permission
        if ($user->hasPermission('leave.edit')) {
            // Check if it's their own leave request
            return (int) $leaveRequest->user_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can cancel a leave request.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        // Can cancel if user has cancel permission and it's their own leave
        if ($user->hasPermission('leave.cancel')) {
            return (int) $leaveRequest->user_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can delete a leave request.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.delete');
    }

    /**
     * Determine if the user can approve leave requests.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermission('leave.approve');
    }

    /**
     * Determine if the user can reject leave requests.
     */
    public function reject(User $user): bool
    {
        return $user->hasPermission('leave.reject');
    }
}
