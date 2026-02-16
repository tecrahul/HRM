<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Notifications\RoleAlertNotification;
use Carbon\Carbon;

class NotificationCenter
{
    public static function syncFor(?User $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        if ($user->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value])) {
            self::syncManagementNotifications($user);
        }

        if ($user->hasRole(UserRole::EMPLOYEE->value)) {
            self::syncEmployeeNotifications($user);
        }
    }

    /**
     * @param list<string> $roles
     */
    public static function notifyRoles(
        array $roles,
        string $key,
        string $title,
        string $message,
        ?string $url = null,
        string $level = 'info',
        int $dedupeWindowMinutes = 30
    ): void {
        $recipients = User::query()
            ->whereIn('role', $roles)
            ->get();

        foreach ($recipients as $recipient) {
            self::push(
                $recipient,
                $key,
                $title,
                $message,
                $url,
                $level,
                $dedupeWindowMinutes
            );
        }
    }

    public static function notifyUser(
        User $recipient,
        string $key,
        string $title,
        string $message,
        ?string $url = null,
        string $level = 'info',
        int $dedupeWindowMinutes = 30
    ): void {
        self::push(
            $recipient,
            $key,
            $title,
            $message,
            $url,
            $level,
            $dedupeWindowMinutes
        );
    }

    private static function syncManagementNotifications(User $viewer): void
    {
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');

        $pendingLeaveCount = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        if ($pendingLeaveCount > 0) {
            self::push(
                $viewer,
                'mgmt.leave.pending',
                'Pending leave approvals',
                "{$pendingLeaveCount} leave request(s) need review.",
                route('modules.leave.index'),
                'warning',
                240
            );
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $pendingPayrollCount = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('payroll_month', [$monthStart, $monthEnd])
            ->where('status', '!=', Payroll::STATUS_PAID)
            ->count();

        if ($pendingPayrollCount > 0) {
            self::push(
                $viewer,
                'mgmt.payroll.pending',
                'Pending payroll actions',
                "{$pendingPayrollCount} payroll record(s) are not paid for this month.",
                route('modules.payroll.index'),
                'warning',
                360
            );
        }

        $upcomingHoliday = Holiday::query()
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->whereDate('holiday_date', '<=', now()->addDays(10)->toDateString())
            ->orderBy('holiday_date')
            ->first();

        if ($upcomingHoliday) {
            $holidayDate = $upcomingHoliday->holiday_date?->format('M d, Y') ?? 'N/A';
            self::push(
                $viewer,
                'mgmt.holiday.upcoming',
                'Upcoming holiday',
                "{$upcomingHoliday->name} on {$holidayDate}.",
                route('modules.holidays.index'),
                'info',
                1440
            );
        }
    }

    private static function syncEmployeeNotifications(User $viewer): void
    {
        $pendingLeaveCount = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        if ($pendingLeaveCount > 0) {
            self::push(
                $viewer,
                'employee.leave.pending',
                'Leave requests pending',
                "{$pendingLeaveCount} of your leave request(s) are pending approval.",
                route('modules.leave.index'),
                'warning',
                360
            );
        }

        $nextApprovedLeave = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->first();

        if ($nextApprovedLeave) {
            $startDate = $nextApprovedLeave->start_date?->format('M d, Y') ?? 'N/A';
            self::push(
                $viewer,
                "employee.leave.upcoming.{$nextApprovedLeave->id}",
                'Upcoming approved leave',
                "Your approved leave starts on {$startDate}.",
                route('modules.leave.index'),
                'info',
                10080
            );
        }

        $latestPayroll = Payroll::query()
            ->where('user_id', $viewer->id)
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->first();

        if ($latestPayroll) {
            $status = str((string) $latestPayroll->status)->replace('_', ' ')->title();
            $month = $latestPayroll->payroll_month instanceof Carbon
                ? $latestPayroll->payroll_month->format('M Y')
                : 'latest month';
            $level = $latestPayroll->status === Payroll::STATUS_PAID ? 'success' : 'warning';

            self::push(
                $viewer,
                "employee.payroll.status.{$latestPayroll->id}",
                "Payroll {$status}",
                "Your payroll status for {$month} is {$status}.",
                route('modules.payroll.index'),
                $level,
                10080
            );
        }
    }

    private static function push(
        User $recipient,
        string $key,
        string $title,
        string $message,
        ?string $url = null,
        string $level = 'info',
        int $dedupeWindowMinutes = 30
    ): void {
        $normalizedLevel = in_array($level, ['info', 'success', 'warning', 'danger'], true)
            ? $level
            : 'info';
        $signature = sha1("{$key}|{$title}|{$message}|{$url}|{$normalizedLevel}");

        $query = $recipient->notifications()
            ->where('type', RoleAlertNotification::class)
            ->where('data->key', $key)
            ->where('data->signature', $signature);

        if ($dedupeWindowMinutes > 0) {
            $query->where('created_at', '>=', now()->subMinutes($dedupeWindowMinutes));
        }

        if ($query->exists()) {
            return;
        }

        $recipient->unreadNotifications()
            ->where('type', RoleAlertNotification::class)
            ->where('data->key', $key)
            ->update(['read_at' => now()]);

        $recipient->notify(new RoleAlertNotification([
            'key' => $key,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'level' => $normalizedLevel,
            'signature' => $signature,
        ]));
    }
}
