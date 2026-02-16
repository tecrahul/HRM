<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use App\Support\NotificationCenter;
use App\Support\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();

        if ($viewer?->hasRole(UserRole::EMPLOYEE->value)) {
            return $this->employeePage($request, $viewer);
        }

        return $this->managementPage($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();

        if (! $viewer) {
            abort(403);
        }

        $isEmployee = $viewer->hasRole(UserRole::EMPLOYEE->value);
        $isManagement = $viewer->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value]);

        if (! $isEmployee && ! $isManagement) {
            abort(403, 'You do not have access to this resource.');
        }

        $rules = [
            'leave_type' => ['required', Rule::in(LeaveRequest::leaveTypes())],
            'day_type' => ['nullable', Rule::in(LeaveRequest::dayTypes())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day_session' => ['nullable', Rule::in(LeaveRequest::halfDaySessions())],
            'reason' => ['required', 'string', 'max:1000'],
        ];

        if ($isManagement) {
            $rules['user_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->where('role', UserRole::EMPLOYEE->value);
                }),
            ];
            $rules['assign_note'] = ['nullable', 'string', 'max:1000'];
        } else {
            $rules['user_id'] = ['prohibited'];
            $rules['assign_note'] = ['prohibited'];
        }

        $validated = $request->validate($rules);
        $dayType = (string) ($validated['day_type'] ?? LeaveRequest::DAY_TYPE_FULL);
        $isHalfDay = $dayType === LeaveRequest::DAY_TYPE_HALF;

        if ($isHalfDay && blank($validated['half_day_session'] ?? null)) {
            return redirect()
                ->route('modules.leave.index')
                ->withErrors(['half_day_session' => 'Please select first half or second half.'])
                ->withInput();
        }

        if ($isHalfDay && $validated['start_date'] !== $validated['end_date']) {
            return redirect()
                ->route('modules.leave.index')
                ->withErrors(['end_date' => 'Half-day leave must be for a single day.'])
                ->withInput();
        }

        $targetUserId = $isManagement ? (int) $validated['user_id'] : (int) $viewer->id;
        $targetUser = User::query()->with('profile')->find($targetUserId);
        $branchName = $targetUser?->profile?->branch;
        $totalDays = $this->calculateTotalDays(
            $validated['start_date'],
            $validated['end_date'],
            $dayType,
            $branchName,
        );

        if ($totalDays <= 0) {
            return redirect()
                ->route('modules.leave.index')
                ->withErrors(['start_date' => 'Selected dates fall on configured holidays. Choose a working day range.'])
                ->withInput();
        }

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $targetUserId,
            'leave_type' => $validated['leave_type'],
            'day_type' => $dayType,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => $isManagement ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_PENDING,
            'half_day_session' => $isHalfDay
                ? ($validated['half_day_session'] ?? null)
                : null,
            'reviewer_id' => $isManagement ? $viewer->id : null,
            'reviewed_at' => $isManagement ? now() : null,
            'review_note' => $isManagement
                ? (blank($validated['assign_note'] ?? null) ? "Assigned by {$viewer->name}." : $validated['assign_note'])
                : null,
        ]);

        $leaveRequest->loadMissing('user');
        $targetName = $leaveRequest->user?->name ?? $viewer->name;
        $dayTypeLabel = str($dayType)->replace('_', ' ')->title();
        $title = $isManagement ? 'Leave assigned' : 'Leave requested';
        $eventKey = $isManagement ? 'leave.assigned' : 'leave.requested';

        ActivityLogger::log(
            $viewer,
            $eventKey,
            $title,
            "{$targetName} • {$dayTypeLabel} • {$leaveRequest->start_date?->format('M d, Y')}",
            '#f59e0b',
            $leaveRequest,
            [
                'leave_type' => (string) $leaveRequest->leave_type,
                'status' => (string) $leaveRequest->status,
            ]
        );

        if ($isManagement && $targetUser instanceof User) {
            NotificationCenter::notifyUser(
                $targetUser,
                "leave.assigned.{$leaveRequest->id}",
                'Leave assigned',
                "A leave request was assigned and approved for {$leaveRequest->start_date?->format('M d, Y')}.",
                route('modules.leave.index'),
                'success',
                0
            );
        }

        if (! $isManagement) {
            NotificationCenter::notifyRoles(
                [UserRole::ADMIN->value, UserRole::HR->value],
                "leave.requested.{$leaveRequest->id}",
                'New leave request',
                "{$targetName} submitted a leave request for review.",
                route('modules.leave.review.form', $leaveRequest),
                'warning',
                0
            );
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('status', $isManagement ? 'Leave assigned successfully.' : 'Leave request submitted successfully.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        $validated = $request->validate([
            'status' => ['required', Rule::in([LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return redirect()
                ->route('modules.leave.review.form', $leaveRequest)
                ->with('error', 'Only pending leave requests can be reviewed.');
        }

        if ($validated['status'] === LeaveRequest::STATUS_REJECTED && blank($validated['review_note'] ?? null)) {
            return redirect()
                ->route('modules.leave.review.form', $leaveRequest)
                ->withErrors(['review_note' => 'Review note is required when rejecting a leave request.'])
                ->withInput();
        }

        $leaveRequest->update([
            'status' => $validated['status'],
            'reviewer_id' => $viewer->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?: null,
        ]);

        $leaveRequest->loadMissing('user');
        $statusLabel = str((string) $leaveRequest->status)->replace('_', ' ')->title();
        $targetName = $leaveRequest->user?->name ?? 'Unknown employee';
        ActivityLogger::log(
            $viewer,
            'leave.reviewed',
            "Leave {$statusLabel}",
            "{$targetName} • reviewed by {$viewer->name}",
            '#f59e0b',
            $leaveRequest
        );

        $reviewedUser = $leaveRequest->user;
        if ($reviewedUser instanceof User) {
            NotificationCenter::notifyUser(
                $reviewedUser,
                "leave.reviewed.{$leaveRequest->id}.{$leaveRequest->status}",
                "Leave {$statusLabel}",
                "Your leave request has been {$statusLabel}.",
                route('modules.leave.index'),
                $leaveRequest->status === LeaveRequest::STATUS_APPROVED ? 'success' : 'warning',
                0
            );
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('status', 'Leave request reviewed successfully.');
    }

    public function reviewPage(Request $request, LeaveRequest $leaveRequest): View|RedirectResponse
    {
        $this->ensureManagementAccess($request);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return redirect()
                ->route('modules.leave.index')
                ->with('error', 'Only pending leave requests can be reviewed.');
        }

        $leaveRequest->loadMissing(['user.profile']);

        return view('modules.leave.review', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $viewer = $request->user();

        if (! $viewer) {
            abort(403);
        }

        if (! $viewer->hasRole(UserRole::EMPLOYEE->value)) {
            abort(403, 'Only employees can cancel leave requests.');
        }

        if ((int) $leaveRequest->user_id !== (int) $viewer->id) {
            abort(403, 'You can only cancel your own leave request.');
        }

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return redirect()
                ->route('modules.leave.index')
                ->with('error', 'Only pending leave requests can be cancelled.');
        }

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_CANCELLED,
            'reviewer_id' => $viewer->id,
            'reviewed_at' => now(),
            'review_note' => 'Cancelled by employee.',
        ]);

        ActivityLogger::log(
            $viewer,
            'leave.cancelled',
            'Leave cancelled',
            "{$viewer->name} cancelled a pending leave",
            '#f59e0b',
            $leaveRequest
        );

        return redirect()
            ->route('modules.leave.index')
            ->with('status', 'Leave request cancelled successfully.');
    }

    private function managementPage(Request $request): View
    {
        $search = (string) $request->string('q');
        $status = (string) $request->string('status');
        $leaveType = (string) $request->string('leave_type');
        $department = (string) $request->string('department');
        $branch = (string) $request->string('branch');
        $employeeId = (int) $request->integer('employee_id');
        $dateFrom = (string) $request->string('date_from');
        $dateTo = (string) $request->string('date_to');

        $employeeRole = UserRole::EMPLOYEE->value;
        $statusOptions = LeaveRequest::statuses();
        $leaveTypeOptions = LeaveRequest::leaveTypes();
        $dayTypeOptions = LeaveRequest::dayTypes();

        $requests = LeaveRequest::query()
            ->with(['user.profile', 'reviewer'])
            ->whereHas('user', function (Builder $query) use ($employeeRole): void {
                $query->where('role', $employeeRole);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                                    $profileQuery
                                        ->where('department', 'like', "%{$search}%")
                                        ->orWhere('branch', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($leaveType !== '' && in_array($leaveType, $leaveTypeOptions, true), function (Builder $query) use ($leaveType): void {
                $query->where('leave_type', $leaveType);
            })
            ->when($employeeId > 0, function (Builder $query) use ($employeeId): void {
                $query->where('user_id', $employeeId);
            })
            ->when($department !== '', function (Builder $query) use ($department): void {
                $query->whereHas('user.profile', function (Builder $profileQuery) use ($department): void {
                    $profileQuery->where('department', $department);
                });
            })
            ->when($branch !== '', function (Builder $query) use ($branch): void {
                $query->whereHas('user.profile', function (Builder $profileQuery) use ($branch): void {
                    $profileQuery->where('branch', $branch);
                });
            })
            ->when($dateFrom !== '', function (Builder $query) use ($dateFrom): void {
                $query->whereDate('start_date', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function (Builder $query) use ($dateTo): void {
                $query->whereDate('end_date', '<=', $dateTo);
            })
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $employeeIds = User::query()
            ->where('role', $employeeRole)
            ->pluck('id');

        $baseQuery = LeaveRequest::query()->whereIn('user_id', $employeeIds);
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $pendingApprovals = LeaveRequest::query()
            ->with(['user.profile'])
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        return view('modules.leave.admin', [
            'requests' => $requests,
            'pendingApprovals' => $pendingApprovals,
            'employees' => $this->employeeOptions(),
            'departmentOptions' => $this->departmentOptions($employeeIds),
            'branchOptions' => $this->branchOptions($employeeIds),
            'statusOptions' => $statusOptions,
            'leaveTypeOptions' => $leaveTypeOptions,
            'dayTypeOptions' => $dayTypeOptions,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', LeaveRequest::STATUS_PENDING)->count(),
                'approved' => (clone $baseQuery)->where('status', LeaveRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $baseQuery)->where('status', LeaveRequest::STATUS_REJECTED)->count(),
                'approvedDaysThisMonth' => (float) ((clone $baseQuery)
                    ->where('status', LeaveRequest::STATUS_APPROVED)
                    ->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->sum('total_days')),
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
                'leave_type' => $leaveType,
                'department' => $department,
                'branch' => $branch,
                'employee_id' => $employeeId > 0 ? (string) $employeeId : '',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    private function employeePage(Request $request, User $viewer): View
    {
        $status = (string) $request->string('status');
        $year = (int) $request->integer('year', (int) now()->format('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->format('Y');
        }

        $statusOptions = LeaveRequest::statuses();
        $leaveTypeOptions = LeaveRequest::leaveTypes();
        $dayTypeOptions = LeaveRequest::dayTypes();

        $requests = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->whereYear('start_date', $year)
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $yearBaseQuery = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->whereYear('start_date', $year);

        $approvedDays = (float) ((clone $yearBaseQuery)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->sum('total_days'));

        $annualAllowance = 24.0;
        $remainingDays = max(0.0, $annualAllowance - $approvedDays);

        $upcomingApproved = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        return view('modules.leave.employee', [
            'viewer' => $viewer,
            'requests' => $requests,
            'upcomingApproved' => $upcomingApproved,
            'statusOptions' => $statusOptions,
            'leaveTypeOptions' => $leaveTypeOptions,
            'dayTypeOptions' => $dayTypeOptions,
            'stats' => [
                'total' => (clone $yearBaseQuery)->count(),
                'pending' => (clone $yearBaseQuery)->where('status', LeaveRequest::STATUS_PENDING)->count(),
                'approved' => (clone $yearBaseQuery)->where('status', LeaveRequest::STATUS_APPROVED)->count(),
                'rejected' => (clone $yearBaseQuery)->where('status', LeaveRequest::STATUS_REJECTED)->count(),
                'approvedDays' => $approvedDays,
                'remainingDays' => $remainingDays,
                'annualAllowance' => $annualAllowance,
            ],
            'filters' => [
                'status' => $status,
                'year' => (string) $year,
            ],
        ]);
    }

    private function ensureManagementAccess(Request $request): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value])) {
            abort(403, 'You do not have access to this resource.');
        }

        return $viewer;
    }

    private function calculateTotalDays(string $startDate, string $endDate, string $dayType, ?string $branchName = null): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $holidayMap = HolidayCalendar::dateMap($start, $end, $branchName, false);

        if ($dayType === LeaveRequest::DAY_TYPE_HALF) {
            if (isset($holidayMap[$start->toDateString()])) {
                return 0.0;
            }

            return 0.5;
        }

        $totalDays = 0.0;
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            if (! isset($holidayMap[$cursor->toDateString()])) {
                $totalDays += 1.0;
            }
            $cursor->addDay();
        }

        return round($totalDays, 2);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function employeeOptions()
    {
        return User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('profile')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param \Illuminate\Support\Collection<int, int> $employeeIds
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function departmentOptions($employeeIds)
    {
        return UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->merge(
                Department::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->pluck('name')
            )
            ->filter(fn ($department): bool => ! blank($department))
            ->map(fn ($department): string => trim((string) $department))
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @param \Illuminate\Support\Collection<int, int> $employeeIds
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function branchOptions($employeeIds)
    {
        return UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->pluck('branch')
            ->merge(
                Branch::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->pluck('name')
            )
            ->filter(fn ($branch): bool => ! blank($branch))
            ->map(fn ($branch): string => trim((string) $branch))
            ->unique()
            ->sort()
            ->values();
    }
}
