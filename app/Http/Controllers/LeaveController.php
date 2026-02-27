<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\FinancialYear;
use App\Support\HolidayCalendar;
use App\Support\NotificationCenter;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    private const ANNUAL_ALLOWANCE = 24.0;

    public function index(Request $request): View|JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer instanceof User) {
            abort(403);
        }

        $capabilities = $this->resolveCapabilities($viewer);
        [$defaultDateFrom, $defaultDateTo] = $this->defaultLeaveFilterDateRange();
        $filters = $this->extractFilters(
            $request,
            $capabilities['isEmployee'],
            $capabilities['canFilterByEmployee'],
            $defaultDateFrom,
            $defaultDateTo
        );
        $perPage = max(5, min(50, (int) $request->integer('per_page', 12)));
        $paginator = $this->paginateLeaves(
            $viewer,
            $capabilities,
            $filters,
            max(1, (int) $request->integer('page', 1)),
            $perPage
        );
        $leavesPayload = $this->serializePaginator($paginator, $viewer, $capabilities, $filters);
        $stats = $this->leaveStats($viewer, $capabilities);

        if ($request->expectsJson()) {
            return response()->json([
                ...$leavesPayload,
                'stats' => $stats,
            ]);
        }

        return view('modules.leave.index', [
            'pagePayload' => [
                'csrfToken' => csrf_token(),
                'routes' => [
                    'list' => route('modules.leave.index'),
                    'create' => route('modules.leave.store'),
                    'updateTemplate' => $this->leaveRouteTemplate('modules.leave.update'),
                    'cancelTemplate' => $this->leaveRouteTemplate('modules.leave.cancel'),
                    'reviewTemplate' => $this->leaveRouteTemplate('modules.leave.review'),
                    'employeeSearch' => route('api.employees.search'),
                ],
                'capabilities' => $capabilities,
                'options' => [
                    'leaveTypes' => $this->optionList(LeaveRequest::leaveTypes()),
                    'dayTypes' => $this->optionList(LeaveRequest::dayTypes()),
                    'halfDaySessions' => $this->optionList(LeaveRequest::halfDaySessions()),
                    'statuses' => $this->optionList(LeaveRequest::statuses()),
                    'createStatuses' => $this->optionList([
                        LeaveRequest::STATUS_PENDING,
                        LeaveRequest::STATUS_APPROVED,
                        LeaveRequest::STATUS_REJECTED,
                    ]),
                ],
                'filters' => $filters,
                'defaults' => [
                    'leaveFilters' => [
                        'date_from' => $defaultDateFrom,
                        'date_to' => $defaultDateTo,
                    ],
                ],
                'leaves' => $leavesPayload,
                'stats' => $stats,
                'currentUser' => [
                    'id' => (int) $viewer->id,
                    'name' => (string) $viewer->full_name,
                    'email' => (string) $viewer->email,
                ],
                'flash' => [
                    'status' => (string) session('status', ''),
                    'error' => (string) session('error', ''),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer instanceof User) {
            abort(403);
        }

        $capabilities = $this->resolveCapabilities($viewer);
        if (! $capabilities['isEmployee'] && ! $capabilities['canAssign']) {
            abort(403, 'You do not have access to this resource.');
        }

        $validated = $request->validate($this->storeRules($capabilities['canAssign']));
        $dayType = (string) ($validated['day_type'] ?? LeaveRequest::DAY_TYPE_FULL);
        $isHalfDay = $dayType === LeaveRequest::DAY_TYPE_HALF;

        if ($isHalfDay && blank($validated['half_day_session'] ?? null)) {
            return $this->validationFailure($request, [
                'half_day_session' => ['Please select first half or second half.'],
            ]);
        }

        if ($isHalfDay && $validated['start_date'] !== $validated['end_date']) {
            return $this->validationFailure($request, [
                'end_date' => ['Half-day leave must be for a single day.'],
            ]);
        }

        $targetUserId = $capabilities['canAssign']
            ? (int) ($validated['user_id'] ?? 0)
            : (int) $viewer->id;
        $targetUser = User::query()->with('profile:user_id,branch')->find($targetUserId);

        if (! $targetUser instanceof User) {
            return $this->validationFailure($request, [
                'user_id' => ['Selected employee is invalid.'],
            ]);
        }

        $totalDays = $this->calculateTotalDays(
            (string) $validated['start_date'],
            (string) $validated['end_date'],
            $dayType,
            (string) ($targetUser->profile?->branch ?? '')
        );

        if ($totalDays <= 0) {
            return $this->validationFailure($request, [
                'start_date' => ['Selected dates fall on configured holidays. Choose a working day range.'],
            ]);
        }

        $requestedLeaveType = (string) $validated['leave_type'];
        if (
            ! $capabilities['canAssign']
            && $requestedLeaveType !== LeaveRequest::TYPE_UNPAID
            && $totalDays > $this->remainingLeaveBalance($targetUser)
        ) {
            return $this->validationFailure($request, [
                'end_date' => ['Requested days exceed available leave balance for the current year.'],
            ]);
        }

        $status = $capabilities['canAssign']
            ? (string) ($validated['status'] ?? LeaveRequest::STATUS_APPROVED)
            : LeaveRequest::STATUS_PENDING;
        $assignNote = trim((string) ($validated['assign_note'] ?? ''));

        if ($status === LeaveRequest::STATUS_REJECTED && $capabilities['canAssign'] && $assignNote === '') {
            return $this->validationFailure($request, [
                'assign_note' => ['Assignment note is required when creating a rejected request.'],
            ]);
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file !== null) {
                $attachmentPath = $file->store('leave-attachments', 'public');
                $attachmentName = $file->getClientOriginalName();
            }
        }

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $targetUserId,
            'leave_type' => $requestedLeaveType,
            'day_type' => $dayType,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => (string) $validated['reason'],
            'status' => $status,
            'half_day_session' => $isHalfDay ? (string) ($validated['half_day_session'] ?? '') : null,
            'reviewer_id' => $capabilities['canAssign'] ? $viewer->id : null,
            'reviewed_at' => $capabilities['canAssign'] ? now() : null,
            'review_note' => $capabilities['canAssign']
                ? ($assignNote === '' ? "Assigned by {$viewer->full_name}." : $assignNote)
                : null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $leaveRequest->loadMissing(['user.profile', 'reviewer']);

        $targetName = $leaveRequest->user?->full_name ?? ($viewer->full_name ?? 'Unknown');
        $dayTypeLabel = Str::of($dayType)->replace('_', ' ')->title()->toString();
        ActivityLogger::log(
            $viewer,
            $capabilities['canAssign'] ? 'leave.assigned' : 'leave.requested',
            $capabilities['canAssign'] ? 'Leave assigned' : 'Leave requested',
            "{$targetName} • {$dayTypeLabel} • {$leaveRequest->start_date?->format('M d, Y')}",
            '#f59e0b',
            $leaveRequest
        );

        if ($capabilities['canAssign']) {
            NotificationCenter::notifyUser(
                $targetUser,
                "leave.assigned.{$leaveRequest->id}",
                'Leave assigned',
                "A leave request was assigned for {$leaveRequest->start_date?->format('M d, Y')}.",
                route('modules.leave.index'),
                $status === LeaveRequest::STATUS_APPROVED ? 'success' : 'warning',
                0
            );
        } else {
            NotificationCenter::notifyRoles(
                [UserRole::ADMIN->value, UserRole::HR->value, UserRole::SUPER_ADMIN->value],
                "leave.requested.{$leaveRequest->id}",
                'New leave request',
                "{$targetName} submitted a leave request for review.",
                route('modules.leave.index'),
                'warning',
                0
            );
        }

        $message = $capabilities['canAssign']
            ? 'Leave assigned successfully.'
            : 'Leave request submitted successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'data' => $this->transformLeave($leaveRequest, $viewer, $capabilities),
            ], 201);
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('status', $message);
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer instanceof User) {
            abort(403);
        }

        $capabilities = $this->resolveCapabilities($viewer);
        $isOwner = (int) $leaveRequest->user_id === (int) $viewer->id;

        if (! $isOwner && ! $capabilities['canAssign']) {
            abort(403, 'You do not have permission to update this leave request.');
        }

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return $this->actionFailure($request, 'Only pending leave requests can be updated.');
        }

        $validated = $request->validate($this->storeRules($capabilities['canAssign']));
        $dayType = (string) ($validated['day_type'] ?? LeaveRequest::DAY_TYPE_FULL);
        $isHalfDay = $dayType === LeaveRequest::DAY_TYPE_HALF;

        if ($isHalfDay && blank($validated['half_day_session'] ?? null)) {
            return $this->validationFailure($request, [
                'half_day_session' => ['Please select first half or second half.'],
            ]);
        }

        if ($isHalfDay && $validated['start_date'] !== $validated['end_date']) {
            return $this->validationFailure($request, [
                'end_date' => ['Half-day leave must be for a single day.'],
            ]);
        }

        $targetUserId = $capabilities['canAssign']
            ? (int) ($validated['user_id'] ?? 0)
            : (int) $viewer->id;
        $targetUser = User::query()->with('profile:user_id,branch')->find($targetUserId);

        if (! $targetUser instanceof User) {
            return $this->validationFailure($request, [
                'user_id' => ['Selected employee is invalid.'],
            ]);
        }

        $totalDays = $this->calculateTotalDays(
            (string) $validated['start_date'],
            (string) $validated['end_date'],
            $dayType,
            (string) ($targetUser->profile?->branch ?? '')
        );

        if ($totalDays <= 0) {
            return $this->validationFailure($request, [
                'start_date' => ['Selected dates fall on configured holidays. Choose a working day range.'],
            ]);
        }

        $requestedLeaveType = (string) $validated['leave_type'];
        if (
            ! $capabilities['canAssign']
            && $requestedLeaveType !== LeaveRequest::TYPE_UNPAID
            && $totalDays > $this->remainingLeaveBalance($targetUser, $leaveRequest)
        ) {
            return $this->validationFailure($request, [
                'end_date' => ['Requested days exceed available leave balance for the current year.'],
            ]);
        }

        $status = $capabilities['canAssign']
            ? (string) ($validated['status'] ?? LeaveRequest::STATUS_PENDING)
            : LeaveRequest::STATUS_PENDING;
        $assignNote = trim((string) ($validated['assign_note'] ?? ''));

        if ($status === LeaveRequest::STATUS_REJECTED && $capabilities['canAssign'] && $assignNote === '') {
            return $this->validationFailure($request, [
                'assign_note' => ['Assignment note is required when setting rejected status.'],
            ]);
        }

        $attachmentPath = $leaveRequest->attachment_path;
        $attachmentName = $leaveRequest->attachment_name;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file !== null) {
                if (is_string($attachmentPath) && $attachmentPath !== '') {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $attachmentPath = $file->store('leave-attachments', 'public');
                $attachmentName = $file->getClientOriginalName();
            }
        }

        $leaveRequest->update([
            'user_id' => $targetUserId,
            'leave_type' => $requestedLeaveType,
            'day_type' => $dayType,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => (string) $validated['reason'],
            'status' => $status,
            'half_day_session' => $isHalfDay ? (string) ($validated['half_day_session'] ?? '') : null,
            'reviewer_id' => $status === LeaveRequest::STATUS_PENDING ? null : $viewer->id,
            'reviewed_at' => $status === LeaveRequest::STATUS_PENDING ? null : now(),
            'review_note' => $status === LeaveRequest::STATUS_PENDING
                ? null
                : ($assignNote === '' ? "Updated by {$viewer->full_name}." : $assignNote),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $leaveRequest->loadMissing(['user.profile', 'reviewer']);

        ActivityLogger::log(
            $viewer,
            'leave.updated',
            'Leave updated',
            "{$leaveRequest->user?->full_name} • {$leaveRequest->start_date?->format('M d, Y')}",
            '#f59e0b',
            $leaveRequest
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Leave request updated successfully.',
                'data' => $this->transformLeave($leaveRequest, $viewer, $capabilities),
            ]);
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('status', 'Leave request updated successfully.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest): RedirectResponse|JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $capabilities = $this->resolveCapabilities($viewer);

        $validated = $request->validate([
            'status' => ['required', Rule::in([LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return $this->actionFailure($request, 'Only pending leave requests can be reviewed.');
        }

        if ($validated['status'] === LeaveRequest::STATUS_REJECTED && blank($validated['review_note'] ?? null)) {
            return $this->validationFailure($request, [
                'review_note' => ['Review note is required when rejecting a leave request.'],
            ]);
        }

        $leaveRequest->update([
            'status' => (string) $validated['status'],
            'reviewer_id' => $viewer->id,
            'reviewed_at' => now(),
            'review_note' => blank($validated['review_note'] ?? null) ? null : (string) $validated['review_note'],
        ]);

        $leaveRequest->loadMissing(['user.profile', 'reviewer']);

        $statusLabel = Str::of((string) $leaveRequest->status)->replace('_', ' ')->title()->toString();
        $targetName = $leaveRequest->user?->full_name ?? 'Unknown employee';
        ActivityLogger::log(
            $viewer,
            'leave.reviewed',
            "Leave {$statusLabel}",
            "{$targetName} • reviewed by {$viewer->full_name}",
            '#f59e0b',
            $leaveRequest
        );

        if ($leaveRequest->user instanceof User) {
            NotificationCenter::notifyUser(
                $leaveRequest->user,
                "leave.reviewed.{$leaveRequest->id}.{$leaveRequest->status}",
                "Leave {$statusLabel}",
                "Your leave request has been {$statusLabel}.",
                route('modules.leave.index'),
                $leaveRequest->status === LeaveRequest::STATUS_APPROVED ? 'success' : 'warning',
                0
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Leave request reviewed successfully.',
                'data' => $this->transformLeave($leaveRequest, $viewer, $capabilities),
            ]);
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

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer instanceof User) {
            abort(403);
        }

        $capabilities = $this->resolveCapabilities($viewer);
        $isOwner = (int) $leaveRequest->user_id === (int) $viewer->id;

        if (! $isOwner && ! $capabilities['canReview']) {
            abort(403, 'You can only cancel your own leave request.');
        }

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return $this->actionFailure($request, 'Only pending leave requests can be cancelled.');
        }

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_CANCELLED,
            'reviewer_id' => $viewer->id,
            'reviewed_at' => now(),
            'review_note' => $isOwner ? 'Cancelled by employee.' : 'Cancelled by management.',
        ]);

        $leaveRequest->loadMissing(['user.profile', 'reviewer']);

        ActivityLogger::log(
            $viewer,
            'leave.cancelled',
            'Leave cancelled',
            "{$viewer->full_name} cancelled a pending leave request",
            '#f59e0b',
            $leaveRequest
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Leave request cancelled successfully.',
                'data' => $this->transformLeave($leaveRequest, $viewer, $capabilities),
            ]);
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('status', 'Leave request cancelled successfully.');
    }

    /**
     * @return array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool}
     */
    private function resolveCapabilities(User $viewer): array
    {
        $isEmployee = $viewer->hasRole(UserRole::EMPLOYEE->value);
        $canReview = $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ]);
        $canAssign = $canReview;
        $canFilterByEmployee = $canReview;

        return [
            'isEmployee' => $isEmployee,
            'canAssign' => $canAssign,
            'canReview' => $canReview,
            'canSetStatus' => $canAssign,
            'canCreate' => $isEmployee || $canAssign,
            'canFilterByEmployee' => $canFilterByEmployee,
        ];
    }

    /**
     * @return array{q:string,status:string,date_from:string,date_to:string,employee_id:string,range_mode:string,range_preset:string}
     */
    private function extractFilters(
        Request $request,
        bool $isEmployee,
        bool $canFilterByEmployee,
        ?string $defaultDateFrom = null,
        ?string $defaultDateTo = null
    ): array
    {
        if ($defaultDateFrom === null || $defaultDateTo === null) {
            [$defaultDateFrom, $defaultDateTo] = $this->defaultLeaveFilterDateRange();
        }

        $status = Str::lower(trim((string) $request->query('status', 'all')));
        if (! in_array($status, array_merge(['all'], LeaveRequest::statuses()), true)) {
            $status = 'all';
        }

        $employeeId = (! $isEmployee && $canFilterByEmployee && (int) $request->integer('employee_id', 0) > 0)
            ? (string) $request->integer('employee_id')
            : '';
        $q = trim((string) $request->query('q', ''));
        $dateFrom = $this->normalizeDateFilter((string) $request->query('date_from', ''));
        $dateTo = $this->normalizeDateFilter((string) $request->query('date_to', ''));

        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = $defaultDateFrom;
            $dateTo = $defaultDateTo;
        } elseif ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            $dateTo = $dateFrom;
        }

        return [
            'q' => $q,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'employee_id' => $employeeId,
            'range_mode' => trim((string) $request->query('range_mode', 'absolute')) ?: 'absolute',
            'range_preset' => trim((string) $request->query('range_preset', '')),
        ];
    }

    /**
     * @param array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool} $capabilities
     * @param array{q:string,status:string,date_from:string,date_to:string,employee_id:string,range_mode:string,range_preset:string} $filters
     */
    private function paginateLeaves(
        User $viewer,
        array $capabilities,
        array $filters,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        return $this->leaveListQuery($viewer, $capabilities, $filters)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool} $capabilities
     * @param array{q:string,status:string,date_from:string,date_to:string,employee_id:string,range_mode:string,range_preset:string} $filters
     */
    private function leaveListQuery(User $viewer, array $capabilities, array $filters): Builder
    {
        $query = LeaveRequest::query()
            ->with(['user.profile', 'reviewer'])
            ->orderByDesc('created_at');

        if ($capabilities['isEmployee']) {
            $query->where('user_id', $viewer->id);
        } else {
            $query->whereHas('user', function (Builder $userQuery): void {
                $userQuery->workforce();
            });

            $employeeId = (int) $filters['employee_id'];
            if ($capabilities['canFilterByEmployee'] && $employeeId > 0) {
                $query->where('user_id', $employeeId);
            }
        }

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('end_date', '<=', $filters['date_to']);
        }

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function (Builder $innerQuery) use ($keyword): void {
                $innerQuery
                    ->where('reason', 'like', "%{$keyword}%")
                    ->orWhere('leave_type', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    /**
     * @param array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool} $capabilities
     * @param array{q:string,status:string,date_from:string,date_to:string,employee_id:string,range_mode:string,range_preset:string} $filters
     * @return array{data:list<array<string,mixed>>,meta:array<string,mixed>,filters:array<string,mixed>}
     */
    private function serializePaginator(
        LengthAwarePaginator $paginator,
        User $viewer,
        array $capabilities,
        array $filters
    ): array {
        return [
            'data' => collect($paginator->items())
                ->map(fn (LeaveRequest $leave): array => $this->transformLeave($leave, $viewer, $capabilities))
                ->values()
                ->all(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @param array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool} $capabilities
     * @return array<string,mixed>
     */
    private function transformLeave(LeaveRequest $leaveRequest, User $viewer, array $capabilities): array
    {
        $isPending = $leaveRequest->status === LeaveRequest::STATUS_PENDING;
        $isOwner = (int) $leaveRequest->user_id === (int) $viewer->id;
        $isHalfDay = ($leaveRequest->day_type ?? LeaveRequest::DAY_TYPE_FULL) === LeaveRequest::DAY_TYPE_HALF;

        return [
            'id' => (int) $leaveRequest->id,
            'employee' => [
                'id' => (int) ($leaveRequest->user?->id ?? 0),
                'name' => (string) ($leaveRequest->user?->full_name ?? 'N/A'),
                'email' => (string) ($leaveRequest->user?->email ?? 'N/A'),
                'department' => (string) ($leaveRequest->user?->profile?->department ?? 'N/A'),
                'branch' => (string) ($leaveRequest->user?->profile?->branch ?? 'N/A'),
                'employeeCode' => (string) ($leaveRequest->user?->profile?->employee_code
                    ?: User::makeEmployeeCode((int) ($leaveRequest->user?->id ?? 0))),
            ],
            'leaveType' => (string) $leaveRequest->leave_type,
            'leaveTypeLabel' => $this->label((string) $leaveRequest->leave_type),
            'dayType' => (string) ($leaveRequest->day_type ?? LeaveRequest::DAY_TYPE_FULL),
            'dayTypeLabel' => $this->label((string) ($leaveRequest->day_type ?? LeaveRequest::DAY_TYPE_FULL)),
            'halfDaySession' => (string) ($leaveRequest->half_day_session ?? ''),
            'halfDaySessionLabel' => $leaveRequest->half_day_session
                ? $this->label((string) $leaveRequest->half_day_session)
                : '',
            'isHalfDay' => $isHalfDay,
            'startDateIso' => $leaveRequest->start_date?->toDateString(),
            'endDateIso' => $leaveRequest->end_date?->toDateString(),
            'dateRangeLabel' => sprintf(
                '%s to %s',
                $leaveRequest->start_date?->format('M d, Y') ?? 'N/A',
                $leaveRequest->end_date?->format('M d, Y') ?? 'N/A'
            ),
            'totalDays' => (float) $leaveRequest->total_days,
            'status' => (string) $leaveRequest->status,
            'statusLabel' => $this->label((string) $leaveRequest->status),
            'reason' => (string) $leaveRequest->reason,
            'reviewNote' => (string) ($leaveRequest->review_note ?? ''),
            'reviewerName' => (string) ($leaveRequest->reviewer?->full_name ?? ''),
            'reviewedAtLabel' => $leaveRequest->reviewed_at?->format('M d, Y h:i A'),
            'createdAtLabel' => $leaveRequest->created_at?->format('M d, Y h:i A'),
            'attachmentName' => (string) ($leaveRequest->attachment_name ?? ''),
            'attachmentUrl' => $leaveRequest->attachment_path
                ? Storage::disk('public')->url((string) $leaveRequest->attachment_path)
                : '',
            'canReview' => $capabilities['canReview'] && $isPending,
            'canDelete' => $isPending && ($isOwner || $capabilities['canReview']),
            'canEdit' => $isPending && ($isOwner || $capabilities['canAssign']),
        ];
    }

    /**
     * @param array{isEmployee:bool,canAssign:bool,canReview:bool,canSetStatus:bool,canCreate:bool,canFilterByEmployee:bool} $capabilities
     * @return array<string,mixed>
     */
    private function leaveStats(User $viewer, array $capabilities): array
    {
        if ($capabilities['isEmployee']) {
            $baseQuery = LeaveRequest::query()->where('user_id', $viewer->id);
            $year = (int) now()->format('Y');
            $approvedDays = (float) (clone $baseQuery)
                ->whereYear('start_date', $year)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->sum('total_days');

            $nextApprovedLeave = (clone $baseQuery)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereDate('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->first();

            return [
                'total' => (int) (clone $baseQuery)->count(),
                'pending' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_PENDING)->count(),
                'approved' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_APPROVED)->count(),
                'rejected' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_REJECTED)->count(),
                'approvedDays' => $approvedDays,
                'remainingDays' => max(0.0, self::ANNUAL_ALLOWANCE - $approvedDays),
                'annualAllowance' => self::ANNUAL_ALLOWANCE,
                'nextLeaveDateIso' => $nextApprovedLeave?->start_date?->toDateString(),
                'nextLeaveDateLabel' => $nextApprovedLeave?->start_date?->format('M d, Y') ?? '',
            ];
        }

        $employeeIds = User::query()->workforce()->pluck('id');
        $baseQuery = LeaveRequest::query()->whereIn('user_id', $employeeIds);
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $nextApprovedLeave = (clone $baseQuery)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->first();

        return [
            'total' => (int) (clone $baseQuery)->count(),
            'pending' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_PENDING)->count(),
            'approved' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_APPROVED)->count(),
            'rejected' => (int) (clone $baseQuery)->where('status', LeaveRequest::STATUS_REJECTED)->count(),
            'approvedDaysThisMonth' => (float) ((clone $baseQuery)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->sum('total_days')),
            'nextLeaveDateIso' => $nextApprovedLeave?->start_date?->toDateString(),
            'nextLeaveDateLabel' => $nextApprovedLeave?->start_date?->format('M d, Y') ?? '',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function storeRules(bool $canAssign): array
    {
        $rules = [
            'leave_type' => ['required', Rule::in(LeaveRequest::leaveTypes())],
            'day_type' => ['required', Rule::in(LeaveRequest::dayTypes())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day_session' => ['nullable', Rule::in(LeaveRequest::halfDaySessions())],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,doc,docx', 'max:5120'],
        ];

        if ($canAssign) {
            $rules['user_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->whereIn('role', [
                        UserRole::EMPLOYEE->value,
                    ]);
                }),
            ];
            $rules['assign_note'] = ['nullable', 'string', 'max:1000'];
            $rules['status'] = ['nullable', Rule::in([
                LeaveRequest::STATUS_PENDING,
                LeaveRequest::STATUS_APPROVED,
                LeaveRequest::STATUS_REJECTED,
            ])];
        } else {
            $rules['user_id'] = ['prohibited'];
            $rules['assign_note'] = ['prohibited'];
            $rules['status'] = ['prohibited'];
        }

        return $rules;
    }

    private function ensureManagementAccess(Request $request): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ])) {
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

    private function remainingLeaveBalance(User $employee, ?LeaveRequest $updatingLeave = null): float
    {
        $query = LeaveRequest::query()
            ->where('user_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', (int) now()->format('Y'));

        if ($updatingLeave instanceof LeaveRequest) {
            $query->where('id', '!=', $updatingLeave->id);
        }

        $approvedDays = (float) $query->sum('total_days');

        return round(max(0.0, self::ANNUAL_ALLOWANCE - $approvedDays), 2);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function defaultLeaveFilterDateRange(): array
    {
        $range = FinancialYear::rangeForStartYear(FinancialYear::currentStartYear());

        return [
            $range['start']->toDateString(),
            $range['end']->toDateString(),
        ];
    }

    private function normalizeDateFilter(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param list<string> $values
     * @return list<array{value:string,label:string}>
     */
    private function optionList(array $values): array
    {
        return collect($values)
            ->map(fn (string $value): array => [
                'value' => $value,
                'label' => $this->label($value),
            ])
            ->values()
            ->all();
    }

    private function label(string $value): string
    {
        return Str::of($value)->replace('_', ' ')->title()->toString();
    }

    private function leaveRouteTemplate(string $routeName): string
    {
        $placeholder = 987654321;

        return str_replace((string) $placeholder, '__LEAVE__', route($routeName, ['leaveRequest' => $placeholder]));
    }

    /**
     * @param array<string,list<string>> $errors
     */
    private function validationFailure(Request $request, array $errors): RedirectResponse|JsonResponse
    {
        $first = (string) collect($errors)->flatten(1)->first();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $first !== '' ? $first : 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        return redirect()
            ->route('modules.leave.index')
            ->withErrors($errors)
            ->withInput();
    }

    private function actionFailure(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->route('modules.leave.index')
            ->with('error', $message);
    }
}
