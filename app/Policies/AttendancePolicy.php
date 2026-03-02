<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        // Optimized: single check for multiple permissions
        return $user->hasAnyPermission([
            'attendance.view',
            'attendance.view.self',
            'attendance.view.department',
            'attendance.view.all',
        ]);
    }

    /**
     * Determine if the user can view a specific attendance record.
     * Uses PermissionScopeResolver for efficient scope checking.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Use PermissionScopeResolver to determine scope
        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'department') {
            // Check if viewer and attendance user are in the same department
            $viewerDepartment = (string) ($user->profile?->department ?? '');
            $attendanceDepartment = (string) ($attendance->user?->profile?->department ?? '');

            return $viewerDepartment !== '' && $viewerDepartment === $attendanceDepartment;
        }

        if ($scope === 'self') {
            return (int) $attendance->user_id === (int) $user->id;
        }

        // No permission
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.create');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.edit');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.delete');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('attendance.approve');
    }

    public function reject(User $user): bool
    {
        return $user->hasPermission('attendance.reject');
    }

    public function lockMonth(User $user): bool
    {
        return $user->hasPermission('attendance.lock.month');
    }

    public function unlockMonth(User $user): bool
    {
        return $user->hasPermission('attendance.unlock.month');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('attendance.export');
    }
}
