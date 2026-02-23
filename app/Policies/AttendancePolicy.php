<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'attendance.view.self',
            'attendance.view.department',
            'attendance.view.all',
        ]);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasPermission('attendance.view.all')) {
            return true;
        }

        if ($user->hasPermission('attendance.view.department')) {
            $viewerDepartment = (string) ($user->profile?->department ?? '');
            $attendanceDepartment = (string) ($attendance->user?->profile?->department ?? '');

            return $viewerDepartment !== '' && $viewerDepartment === $attendanceDepartment;
        }

        return $user->hasPermission('attendance.view.self') && (int) $attendance->user_id === (int) $user->id;
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
