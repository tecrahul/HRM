<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceMonthLock;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! $viewer->hasAnyPermission([
            'attendance.view.self',
            'attendance.view.department',
            'attendance.view.all',
        ])) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view attendance records.');
        }

        $payload = $this->buildIndexPayload($request, $viewer);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('modules.attendance.index', [
            'payload' => $payload,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.create')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to mark attendance.');
        }

        if ($this->isSelfOnlyViewer($viewer)) {
            return $this->actionFailure(
                $request,
                'Employees can only punch in/out. Date, time, and status editing is not allowed.',
                Response::HTTP_FORBIDDEN
            );
        }

        $validated = $this->validateAttendancePayload($request);
        $targetUserId = $this->resolveTargetUserId($viewer, $validated);

        if (! $this->canAccessUserAttendance($viewer, $targetUserId)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot mark attendance for this employee.');
        }

        $attendanceDate = (string) $validated['attendance_date'];

        if ($this->isMonthLockedForDate($attendanceDate)) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        [$checkInAt, $checkOutAt, $workMinutes] = $this->normalizeTimes(
            $attendanceDate,
            $validated['check_in_time'] ?? null,
            $validated['check_out_time'] ?? null,
        );

        $approvalStatus = $viewer->can('attendance.approve')
            ? Attendance::APPROVAL_APPROVED
            : Attendance::APPROVAL_PENDING;

        $existing = Attendance::query()
            ->where('user_id', $targetUserId)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        $beforeValues = $existing?->only([
            'user_id',
            'attendance_date',
            'status',
            'check_in_at',
            'check_out_at',
            'work_minutes',
            'approval_status',
            'notes',
        ]) ?? [];

        $attendance = Attendance::query()->updateOrCreate(
            [
                'user_id' => $targetUserId,
                'attendance_date' => $attendanceDate,
            ],
            [
                'status' => (string) $validated['status'],
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'work_minutes' => $workMinutes,
                'notes' => $validated['notes'] ?: null,
                'marked_by_user_id' => $viewer->id,
                'approval_status' => $approvalStatus,
                'approval_note' => null,
                'approved_by_user_id' => $approvalStatus === Attendance::APPROVAL_APPROVED ? $viewer->id : null,
                'approved_at' => $approvalStatus === Attendance::APPROVAL_APPROVED ? now() : null,
                'rejected_by_user_id' => null,
                'rejected_at' => null,
                'correction_requested_by_user_id' => null,
                'correction_requested_at' => null,
                'correction_reason' => null,
                'requested_check_in_at' => null,
                'requested_check_out_at' => null,
                'requested_work_minutes' => null,
            ]
        );

        $this->logAudit(
            $viewer,
            $attendance,
            $attendance->wasRecentlyCreated ? 'attendance.created' : 'attendance.updated',
            $beforeValues,
            $attendance->only([
                'user_id',
                'attendance_date',
                'status',
                'check_in_at',
                'check_out_at',
                'work_minutes',
                'approval_status',
                'notes',
            ])
        );

        return $this->actionSuccess(
            $request,
            $attendance->wasRecentlyCreated ? 'Attendance marked successfully.' : 'Attendance updated successfully.',
            ['record' => $this->mapAttendanceForResponse($attendance->fresh(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy']), $viewer, false)]
        );
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.edit')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to edit attendance records.');
        }

        if ($this->isSelfOnlyViewer($viewer)) {
            return $this->actionFailure(
                $request,
                'Employees can only punch in/out. Date, time, and status editing is not allowed.',
                Response::HTTP_FORBIDDEN
            );
        }

        if (! $this->canAccessAttendance($viewer, $attendance)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot edit this attendance record.');
        }

        $this->authorize('update', $attendance);

        $validated = $this->validateAttendancePayload($request);
        $targetUserId = $this->resolveTargetUserId($viewer, $validated);

        if (! $this->canAccessUserAttendance($viewer, $targetUserId)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot move this record to the selected employee.');
        }

        $attendanceDate = (string) $validated['attendance_date'];

        if ($this->isMonthLockedForDate($attendanceDate) || $this->isMonthLockedForDate($attendance->attendance_date?->toDateString() ?? $attendanceDate)) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        $duplicateExists = Attendance::query()
            ->where('user_id', $targetUserId)
            ->whereDate('attendance_date', $attendanceDate)
            ->whereKeyNot($attendance->id)
            ->exists();

        if ($duplicateExists) {
            return $this->actionFailure($request, 'Attendance already exists for this employee and date.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$checkInAt, $checkOutAt, $workMinutes] = $this->normalizeTimes(
            $attendanceDate,
            $validated['check_in_time'] ?? null,
            $validated['check_out_time'] ?? null,
        );

        $beforeValues = $attendance->only([
            'user_id',
            'attendance_date',
            'status',
            'check_in_at',
            'check_out_at',
            'work_minutes',
            'approval_status',
            'notes',
        ]);

        $attendance->update([
            'user_id' => $targetUserId,
            'attendance_date' => $attendanceDate,
            'status' => (string) $validated['status'],
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'work_minutes' => $workMinutes,
            'notes' => $validated['notes'] ?: null,
            'marked_by_user_id' => $viewer->id,
            'approval_status' => Attendance::APPROVAL_APPROVED,
            'approval_note' => null,
            'approved_by_user_id' => $viewer->id,
            'approved_at' => now(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'correction_requested_by_user_id' => null,
            'correction_requested_at' => null,
            'correction_reason' => null,
            'requested_check_in_at' => null,
            'requested_check_out_at' => null,
            'requested_work_minutes' => null,
        ]);

        $attendance->loadMissing(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy']);

        $this->logAudit(
            $viewer,
            $attendance,
            'attendance.updated',
            $beforeValues,
            $attendance->only([
                'user_id',
                'attendance_date',
                'status',
                'check_in_at',
                'check_out_at',
                'work_minutes',
                'approval_status',
                'notes',
            ])
        );

        return $this->actionSuccess($request, 'Attendance record updated successfully.', [
            'record' => $this->mapAttendanceForResponse($attendance, $viewer, false),
        ]);
    }

    public function destroy(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.delete')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to delete attendance records.');
        }

        if (! $this->canAccessAttendance($viewer, $attendance)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot delete this attendance record.');
        }

        $this->authorize('delete', $attendance);

        if ($this->isMonthLockedForDate($attendance->attendance_date?->toDateString() ?? now()->toDateString())) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        $beforeValues = $attendance->only([
            'user_id',
            'attendance_date',
            'status',
            'check_in_at',
            'check_out_at',
            'work_minutes',
            'approval_status',
            'notes',
        ]);

        $attendanceId = $attendance->id;
        $attendance->delete();

        $this->logAudit(
            $viewer,
            null,
            'attendance.deleted',
            $beforeValues,
            ['deleted_attendance_id' => $attendanceId],
            ['attendance_id' => $attendanceId]
        );

        return $this->actionSuccess($request, 'Attendance record deleted successfully.');
    }

    public function approve(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.approve')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to approve attendance.');
        }

        if (! $this->canAccessAttendance($viewer, $attendance)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot approve this attendance record.');
        }

        if ($this->isMonthLockedForDate($attendance->attendance_date?->toDateString() ?? now()->toDateString())) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        if ((string) $attendance->approval_status !== Attendance::APPROVAL_PENDING) {
            return $this->actionFailure($request, 'Only pending attendance records can be approved.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $beforeValues = $attendance->only([
            'status',
            'check_in_at',
            'check_out_at',
            'work_minutes',
            'approval_status',
            'approval_note',
            'requested_check_in_at',
            'requested_check_out_at',
            'requested_work_minutes',
            'correction_reason',
        ]);

        $nextCheckIn = $attendance->requested_check_in_at ?? $attendance->check_in_at;
        $nextCheckOut = $attendance->requested_check_out_at ?? $attendance->check_out_at;
        $nextWorkMinutes = $attendance->requested_work_minutes;

        if ($nextWorkMinutes === null) {
            if ($nextCheckIn !== null && $nextCheckOut !== null) {
                if ($nextCheckOut->lessThanOrEqualTo($nextCheckIn)) {
                    return $this->actionFailure($request, 'Requested check-out time must be after check-in time.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $nextWorkMinutes = $nextCheckIn->diffInMinutes($nextCheckOut);
            } else {
                $nextWorkMinutes = null;
            }
        }

        $attendance->update([
            'check_in_at' => $nextCheckIn,
            'check_out_at' => $nextCheckOut,
            'work_minutes' => $nextWorkMinutes,
            'approval_status' => Attendance::APPROVAL_APPROVED,
            'approval_note' => $validated['note'] ?: null,
            'approved_by_user_id' => $viewer->id,
            'approved_at' => now(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'requested_check_in_at' => null,
            'requested_check_out_at' => null,
            'requested_work_minutes' => null,
            'correction_requested_by_user_id' => null,
            'correction_requested_at' => null,
        ]);

        $attendance->loadMissing(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy']);

        $this->logAudit(
            $viewer,
            $attendance,
            'attendance.approved',
            $beforeValues,
            $attendance->only([
                'status',
                'check_in_at',
                'check_out_at',
                'work_minutes',
                'approval_status',
                'approval_note',
                'correction_reason',
            ])
        );

        return $this->actionSuccess($request, 'Attendance approved successfully.', [
            'record' => $this->mapAttendanceForResponse($attendance, $viewer, false),
        ]);
    }

    public function reject(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.reject')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to reject attendance.');
        }

        if (! $this->canAccessAttendance($viewer, $attendance)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot reject this attendance record.');
        }

        if ($this->isMonthLockedForDate($attendance->attendance_date?->toDateString() ?? now()->toDateString())) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        if ((string) $attendance->approval_status !== Attendance::APPROVAL_PENDING) {
            return $this->actionFailure($request, 'Only pending attendance records can be rejected.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $beforeValues = $attendance->only([
            'approval_status',
            'approval_note',
            'requested_check_in_at',
            'requested_check_out_at',
            'requested_work_minutes',
            'correction_reason',
        ]);

        $attendance->update([
            'approval_status' => Attendance::APPROVAL_REJECTED,
            'approval_note' => (string) $validated['reason'],
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => $viewer->id,
            'rejected_at' => now(),
        ]);

        $attendance->loadMissing(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy']);

        $this->logAudit(
            $viewer,
            $attendance,
            'attendance.rejected',
            $beforeValues,
            $attendance->only([
                'approval_status',
                'approval_note',
                'correction_reason',
            ])
        );

        return $this->actionSuccess($request, 'Attendance rejected successfully.', [
            'record' => $this->mapAttendanceForResponse($attendance, $viewer, false),
        ]);
    }

    public function requestCorrection(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.create')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to submit correction requests.');
        }

        if ((int) $attendance->user_id !== (int) $viewer->id) {
            abort(Response::HTTP_FORBIDDEN, 'You can only request correction for your own attendance.');
        }

        if ($this->isMonthLockedForDate($attendance->attendance_date?->toDateString() ?? now()->toDateString())) {
            return $this->actionFailure($request, 'Attendance month is locked. Locked months cannot be edited.');
        }

        $validated = $request->validate([
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        [$requestedCheckIn, $requestedCheckOut, $requestedWorkMinutes] = $this->normalizeTimes(
            $attendance->attendance_date?->toDateString() ?? now()->toDateString(),
            $validated['check_in_time'] ?? null,
            $validated['check_out_time'] ?? null,
        );

        $beforeValues = $attendance->only([
            'approval_status',
            'approval_note',
            'requested_check_in_at',
            'requested_check_out_at',
            'requested_work_minutes',
            'correction_reason',
            'correction_requested_by_user_id',
            'correction_requested_at',
        ]);

        $attendance->update([
            'approval_status' => Attendance::APPROVAL_PENDING,
            'approval_note' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'correction_requested_by_user_id' => $viewer->id,
            'correction_requested_at' => now(),
            'correction_reason' => (string) $validated['reason'],
            'requested_check_in_at' => $requestedCheckIn,
            'requested_check_out_at' => $requestedCheckOut,
            'requested_work_minutes' => $requestedWorkMinutes,
        ]);

        $attendance->loadMissing(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy']);

        $this->logAudit(
            $viewer,
            $attendance,
            'attendance.correction_submitted',
            $beforeValues,
            $attendance->only([
                'approval_status',
                'requested_check_in_at',
                'requested_check_out_at',
                'requested_work_minutes',
                'correction_reason',
                'correction_requested_by_user_id',
                'correction_requested_at',
            ])
        );

        return $this->actionSuccess($request, 'Correction request submitted and pending approval.', [
            'record' => $this->mapAttendanceForResponse($attendance, $viewer, false),
        ]);
    }

    public function lockMonth(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.lock.month')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to lock attendance month.');
        }

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $attendanceMonth = Carbon::createFromFormat('Y-m', (string) $validated['month'])->startOfMonth();

        $activeLock = AttendanceMonthLock::query()
            ->whereDate('attendance_month', $attendanceMonth->toDateString())
            ->whereNull('unlocked_at')
            ->first();

        if ($activeLock instanceof AttendanceMonthLock) {
            return $this->actionFailure($request, 'This month is already locked.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lock = AttendanceMonthLock::query()->create([
            'attendance_month' => $attendanceMonth->toDateString(),
            'locked_by_user_id' => $viewer->id,
            'locked_at' => now(),
            'metadata' => [
                'source' => 'attendance_module',
                'month_label' => $attendanceMonth->format('F Y'),
            ],
        ]);

        $this->logAudit(
            $viewer,
            null,
            'attendance.month_locked',
            [],
            ['attendance_month' => $attendanceMonth->toDateString()],
            ['lock_id' => $lock->id]
        );

        return $this->actionSuccess($request, 'Attendance month locked successfully.');
    }

    public function unlockMonth(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.unlock.month')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to unlock attendance month.');
        }

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $attendanceMonth = Carbon::createFromFormat('Y-m', (string) $validated['month'])->startOfMonth();

        $activeLock = AttendanceMonthLock::query()
            ->whereDate('attendance_month', $attendanceMonth->toDateString())
            ->whereNull('unlocked_at')
            ->first();

        if (! $activeLock instanceof AttendanceMonthLock) {
            return $this->actionFailure($request, 'No active lock found for selected month.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $beforeValues = $activeLock->only([
            'attendance_month',
            'locked_by_user_id',
            'locked_at',
            'unlocked_at',
            'unlock_reason',
        ]);

        $activeLock->update([
            'unlocked_by_user_id' => $viewer->id,
            'unlocked_at' => now(),
            'unlock_reason' => (string) $validated['reason'],
        ]);

        $this->logAudit(
            $viewer,
            null,
            'attendance.month_unlocked',
            $beforeValues,
            $activeLock->only([
                'attendance_month',
                'locked_by_user_id',
                'locked_at',
                'unlocked_by_user_id',
                'unlocked_at',
                'unlock_reason',
            ]),
            ['lock_id' => $activeLock->id]
        );

        return $this->actionSuccess($request, 'Attendance month unlocked successfully.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.export')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to export attendance records.');
        }

        $filters = $this->resolveFilters($request, $viewer);

        $query = $this->buildFilteredQuery($viewer, $filters)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        $filename = 'attendance-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if (! is_resource($handle)) {
                return;
            }

            fputcsv($handle, [
                'Employee',
                'Email',
                'Branch',
                'Department',
                'Date',
                'Status',
                'Check In',
                'Check Out',
                'Hours',
                'Approval Status',
                'Approval Note',
            ]);

            $query->chunk(200, function ($records) use ($handle): void {
                foreach ($records as $record) {
                    $profile = $record->user?->profile;
                    fputcsv($handle, [
                        (string) ($record->user?->name ?? ''),
                        (string) ($record->user?->email ?? ''),
                        (string) ($profile?->branch ?? ''),
                        (string) ($profile?->department ?? ''),
                        $record->attendance_date?->format('Y-m-d') ?? '',
                        Str::headline((string) $record->status),
                        $record->check_in_at?->format('H:i') ?? '',
                        $record->check_out_at?->format('H:i') ?? '',
                        $record->work_minutes !== null ? number_format($record->work_minutes / 60, 2) : '',
                        Str::headline((string) $record->approval_status),
                        (string) ($record->approval_note ?? ''),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function searchEmployees(Request $request): JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyPermission([
            'attendance.view.self',
            'attendance.view.department',
            'attendance.view.all',
        ])) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to search employees.');
        }

        $filters = $this->resolveFilters($request, $viewer);
        $query = trim((string) $request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $requiresDepartment = $viewer->can('attendance.view.department') || $viewer->can('attendance.view.all');
        if ($requiresDepartment && $filters['department'] === '') {
            return response()->json([]);
        }

        $records = $this->workforceScope($viewer)
            ->with('profile')
            ->when($filters['branch'] !== '', function (Builder $queryBuilder) use ($filters): void {
                $queryBuilder->whereHas('profile', function (Builder $profileQuery) use ($filters): void {
                    $profileQuery->where('branch', $filters['branch']);
                });
            })
            ->when($filters['department'] !== '', function (Builder $queryBuilder) use ($filters): void {
                $queryBuilder->whereHas('profile', function (Builder $profileQuery) use ($filters): void {
                    $profileQuery->where('department', $filters['department']);
                });
            })
            ->where(function (Builder $queryBuilder) use ($query): void {
                $queryBuilder
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(static fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'department' => (string) ($user->profile?->department ?? ''),
                'branch' => (string) ($user->profile?->branch ?? ''),
                'employee_code' => User::makeEmployeeCode((int) $user->id),
            ])
            ->values()
            ->all();

        return response()->json($records);
    }

    public function checkIn(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.create')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to check in.');
        }

        $today = now()->toDateString();

        if ($this->isMonthLockedForDate($today)) {
            return $this->actionFailure($request, 'Attendance month is locked.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $record = Attendance::query()->firstOrNew([
            'user_id' => $viewer->id,
            'attendance_date' => $today,
        ]);

        if ($record->exists && $record->check_in_at !== null) {
            return $this->actionFailure($request, 'You have already checked in today.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $beforeValues = $record->exists
            ? $record->only(['status', 'check_in_at', 'check_out_at', 'work_minutes', 'approval_status', 'notes'])
            : [];

        $record->status = in_array((string) $record->status, Attendance::statuses(), true)
            ? (string) $record->status
            : Attendance::STATUS_PRESENT;
        $record->check_in_at = now();
        $record->marked_by_user_id = $viewer->id;
        $record->notes = $validated['notes'] ?: $record->notes;
        $record->approval_status = Attendance::APPROVAL_PENDING;
        $record->approved_by_user_id = null;
        $record->approved_at = null;
        $record->rejected_by_user_id = null;
        $record->rejected_at = null;
        $record->save();

        $this->logAudit(
            $viewer,
            $record,
            'attendance.check_in',
            $beforeValues,
            $record->only(['status', 'check_in_at', 'check_out_at', 'work_minutes', 'approval_status', 'notes'])
        );

        return $this->actionSuccess($request, 'Checked in successfully. Attendance is pending approval.');
    }

    public function checkOut(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->can('attendance.create')) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to check out.');
        }

        $today = now()->toDateString();

        if ($this->isMonthLockedForDate($today)) {
            return $this->actionFailure($request, 'Attendance month is locked.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $record = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $record || $record->check_in_at === null) {
            return $this->actionFailure($request, 'Please check in before checking out.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($record->check_out_at !== null) {
            return $this->actionFailure($request, 'You have already checked out today.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $checkOutAt = now();
        if ($checkOutAt->lessThanOrEqualTo($record->check_in_at)) {
            return $this->actionFailure($request, 'Invalid check-out time.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $beforeValues = $record->only(['status', 'check_in_at', 'check_out_at', 'work_minutes', 'approval_status', 'notes']);

        $record->check_out_at = $checkOutAt;
        $record->work_minutes = $record->check_in_at->diffInMinutes($checkOutAt);
        $record->status = in_array((string) $record->status, Attendance::statuses(), true)
            ? (string) $record->status
            : Attendance::STATUS_PRESENT;
        $record->marked_by_user_id = $viewer->id;
        $record->notes = $validated['notes'] ?: $record->notes;
        $record->approval_status = Attendance::APPROVAL_PENDING;
        $record->approved_by_user_id = null;
        $record->approved_at = null;
        $record->rejected_by_user_id = null;
        $record->rejected_at = null;
        $record->save();

        $this->logAudit(
            $viewer,
            $record,
            'attendance.check_out',
            $beforeValues,
            $record->only(['status', 'check_in_at', 'check_out_at', 'work_minutes', 'approval_status', 'notes'])
        );

        return $this->actionSuccess($request, 'Checked out successfully. Attendance is pending approval.');
    }

    private function buildIndexPayload(Request $request, User $viewer): array
    {
        $filters = $this->resolveFilters($request, $viewer);

        $query = $this->buildFilteredQuery($viewer, $filters);
        $records = $query
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $capabilities = $this->resolveCapabilities($viewer);

        $months = $records->getCollection()
            ->map(static fn (Attendance $attendance): ?string => $attendance->attendance_date?->copy()->startOfMonth()->toDateString())
            ->filter(static fn (?string $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values();

        $lockedMonths = AttendanceMonthLock::query()
            ->whereIn('attendance_month', $months)
            ->whereNull('unlocked_at')
            ->pluck('attendance_month')
            ->map(static fn ($value): string => Carbon::parse((string) $value)->toDateString())
            ->all();

        $lockedMonthMap = array_fill_keys($lockedMonths, true);

        $data = $records->getCollection()
            ->map(fn (Attendance $attendance): array => $this->mapAttendanceForResponse(
                $attendance,
                $viewer,
                isset($lockedMonthMap[$attendance->attendance_date?->copy()->startOfMonth()->toDateString() ?? ''])
            ))
            ->values()
            ->all();

        $stats = $this->buildStats($viewer);

        return [
            'data' => $data,
            'meta' => [
                'currentPage' => $records->currentPage(),
                'lastPage' => $records->lastPage(),
                'perPage' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
            'filters' => $filters,
            'options' => $this->buildOptions($viewer),
            'stats' => $stats,
            'capabilities' => $capabilities,
            'punch' => $this->resolvePunchState($viewer),
            'locks' => [
                'selectedMonthLocked' => $this->isMonthLockedForDate($this->selectedFilterDate($filters)),
            ],
            'routes' => [
                'list' => route('modules.attendance.index'),
                'store' => route('modules.attendance.store'),
                'updateTemplate' => route('modules.attendance.update', ['attendance' => '__ATTENDANCE__']),
                'deleteTemplate' => route('modules.attendance.destroy', ['attendance' => '__ATTENDANCE__']),
                'approveTemplate' => route('modules.attendance.approve', ['attendance' => '__ATTENDANCE__']),
                'rejectTemplate' => route('modules.attendance.reject', ['attendance' => '__ATTENDANCE__']),
                'correctionTemplate' => route('modules.attendance.correction', ['attendance' => '__ATTENDANCE__']),
                'lockMonth' => route('modules.attendance.lock-month'),
                'unlockMonth' => route('modules.attendance.unlock-month'),
                'exportCsv' => route('modules.attendance.export-csv'),
                'employeeSearch' => route('modules.attendance.employee-search'),
                'checkIn' => route('modules.attendance.check-in'),
                'checkOut' => route('modules.attendance.check-out'),
            ],
            'currentUser' => [
                'id' => $viewer->id,
                'name' => $viewer->name,
                'email' => $viewer->email,
                'department' => $viewer->profile?->department,
                'branch' => $viewer->profile?->branch,
                'role' => $this->resolveViewerRole($viewer),
            ],
            'flash' => [
                'status' => session('status'),
                'error' => session('error'),
            ],
        ];
    }

    /**
     * @param array<string, string> $filters
     */
    private function buildFilteredQuery(User $viewer, array $filters): Builder
    {
        $query = Attendance::query()
            ->with([
                'user.profile',
                'markedBy',
                'approvedBy',
                'rejectedBy',
                'correctionRequestedBy',
            ])
            ->whereHas('user', function (Builder $query): void {
                $query->workforce();
            });

        $this->applyAccessScope($query, $viewer);

        $status = trim($filters['status']);
        $approvalStatus = trim($filters['approval_status']);
        $attendanceDate = trim($filters['attendance_date']);
        $useDateRange = $filters['use_date_range'] === '1';
        $dateFrom = trim($filters['date_from']);
        $dateTo = trim($filters['date_to']);
        $department = trim($filters['department']);
        $branch = trim($filters['branch']);
        $employeeId = (int) $filters['employee_id'];

        if ($status !== '' && in_array($status, Attendance::statuses(), true)) {
            $query->where('status', $status);
        }

        if ($approvalStatus !== '' && in_array($approvalStatus, Attendance::approvalStatuses(), true)) {
            $query->where('approval_status', $approvalStatus);
        }

        if ($useDateRange && $dateFrom !== '' && $dateTo !== '') {
            $query->whereBetween('attendance_date', [$dateFrom, $dateTo]);
        } elseif ($attendanceDate !== '') {
            $query->whereDate('attendance_date', $attendanceDate);
        }

        if ($employeeId > 0) {
            $query->where('user_id', $employeeId);
        }

        if ($department !== '') {
            $query->whereHas('user.profile', function (Builder $profileQuery) use ($department): void {
                $profileQuery->where('department', $department);
            });
        }

        if ($branch !== '') {
            $query->whereHas('user.profile', function (Builder $profileQuery) use ($branch): void {
                $profileQuery->where('branch', $branch);
            });
        }

        return $query;
    }

    private function applyAccessScope(Builder $query, User $viewer): void
    {
        if ($viewer->can('attendance.view.all')) {
            return;
        }

        if ($viewer->can('attendance.view.department')) {
            $department = (string) ($viewer->profile?->department ?? '');

            if ($department === '') {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereHas('user.profile', function (Builder $profileQuery) use ($department): void {
                $profileQuery->where('department', $department);
            });

            return;
        }

        $query->where('user_id', $viewer->id);
    }

    private function canAccessAttendance(User $viewer, Attendance $attendance): bool
    {
        if ($viewer->can('attendance.view.all')) {
            return true;
        }

        if ($viewer->can('attendance.view.department')) {
            $viewerDepartment = (string) ($viewer->profile?->department ?? '');
            $attendanceDepartment = (string) ($attendance->user?->profile?->department ?? '');

            return $viewerDepartment !== '' && $viewerDepartment === $attendanceDepartment;
        }

        return (int) $attendance->user_id === (int) $viewer->id;
    }

    private function canAccessUserAttendance(User $viewer, int $targetUserId): bool
    {
        if ($targetUserId <= 0) {
            return false;
        }

        if ($viewer->can('attendance.view.all')) {
            return User::query()->workforce()->whereKey($targetUserId)->exists();
        }

        if ($viewer->can('attendance.view.department')) {
            $department = (string) ($viewer->profile?->department ?? '');
            if ($department === '') {
                return false;
            }

            return User::query()
                ->workforce()
                ->whereKey($targetUserId)
                ->whereHas('profile', function (Builder $query) use ($department): void {
                    $query->where('department', $department);
                })
                ->exists();
        }

        return (int) $viewer->id === $targetUserId;
    }

    private function buildStats(User $viewer): array
    {
        $workforceQuery = $this->workforceScope($viewer);
        $employeeIds = $workforceQuery->pluck('id');

        $today = now()->toDateString();

        $todayBase = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $today);

        $totalEmployees = (int) $employeeIds->count();
        $presentToday = (clone $todayBase)
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_REMOTE,
            ])
            ->count();

        $absentToday = (clone $todayBase)
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();

        $pendingApprovalsQuery = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->where('approval_status', Attendance::APPROVAL_PENDING);

        $pendingApprovals = $viewer->can('attendance.approve')
            ? (int) $pendingApprovalsQuery->count()
            : (int) $pendingApprovalsQuery->where('user_id', $viewer->id)->count();

        return [
            'totalEmployees' => $totalEmployees,
            'presentToday' => (int) $presentToday,
            'absentToday' => (int) $absentToday,
            'pendingApprovals' => $pendingApprovals,
        ];
    }

    private function workforceScope(User $viewer): Builder
    {
        $query = User::query()->workforce();

        if ($viewer->can('attendance.view.all')) {
            return $query;
        }

        if ($viewer->can('attendance.view.department')) {
            $department = (string) ($viewer->profile?->department ?? '');

            if ($department === '') {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('profile', function (Builder $profileQuery) use ($department): void {
                $profileQuery->where('department', $department);
            });
        }

        return $query->whereKey($viewer->id);
    }

    private function buildOptions(User $viewer): array
    {
        $statusOptions = collect(Attendance::statuses())
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => Str::headline(str_replace('_', ' ', $status)),
            ])
            ->values()
            ->all();

        $approvalStatusOptions = collect(Attendance::approvalStatuses())
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => Str::headline(str_replace('_', ' ', $status)),
            ])
            ->values()
            ->all();

        $accessibleUserIds = $this->workforceScope($viewer)->pluck('id');

        $departmentOptions = UserProfile::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->map(static fn ($department): string => trim((string) $department))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $branchOptions = UserProfile::query()
            ->whereIn('user_id', $accessibleUserIds)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->pluck('branch')
            ->map(static fn ($branch): string => trim((string) $branch))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'statuses' => $statusOptions,
            'approvalStatuses' => $approvalStatusOptions,
            'departments' => $departmentOptions,
            'branches' => $branchOptions,
        ];
    }

    private function resolveCapabilities(User $viewer): array
    {
        $isEmployeeOnly = $this->isSelfOnlyViewer($viewer);

        return [
            'canViewSelf' => $viewer->can('attendance.view.self'),
            'canViewDepartment' => $viewer->can('attendance.view.department'),
            'canViewAll' => $viewer->can('attendance.view.all'),
            'canCreate' => $viewer->can('attendance.create') && ! $isEmployeeOnly,
            'canPunchSelf' => $viewer->can('attendance.create') && $isEmployeeOnly,
            'canEdit' => $viewer->can('attendance.edit'),
            'canDelete' => $viewer->can('attendance.delete'),
            'canApprove' => $viewer->can('attendance.approve'),
            'canReject' => $viewer->can('attendance.reject'),
            'canLockMonth' => $viewer->can('attendance.lock.month'),
            'canUnlockMonth' => $viewer->can('attendance.unlock.month'),
            'canExport' => $viewer->can('attendance.export'),
            'canRequestCorrection' => $viewer->can('attendance.create') && ! $isEmployeeOnly,
            'showBranchColumn' => $viewer->can('attendance.view.all'),
            'showDepartmentColumn' => $viewer->can('attendance.view.all') || $viewer->can('attendance.view.department'),
            'isEmployeeOnly' => $isEmployeeOnly,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resolveFilters(Request $request, User $viewer): array
    {
        $today = now()->toDateString();
        $useDateRange = $request->has('use_date_range')
            ? $request->boolean('use_date_range')
            : true;

        $attendanceDate = $this->normalizeDateFilter((string) $request->string('attendance_date'), $today);
        $dateFrom = $this->normalizeDateFilter((string) $request->string('date_from'));
        $dateTo = $this->normalizeDateFilter((string) $request->string('date_to'));

        if ($useDateRange) {
            if ($dateFrom === '' && $dateTo === '') {
                $dateFrom = $attendanceDate;
                $dateTo = $attendanceDate;
            } elseif ($dateFrom === '') {
                $dateFrom = $dateTo;
            } elseif ($dateTo === '') {
                $dateTo = $dateFrom;
            }

            if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
                $dateTo = $dateFrom;
            }
        } else {
            $dateFrom = '';
            $dateTo = '';
        }

        $status = trim((string) $request->string('status'));
        if (! in_array($status, Attendance::statuses(), true)) {
            $status = '';
        }

        $approvalStatus = trim((string) $request->string('approval_status'));
        if (! in_array($approvalStatus, Attendance::approvalStatuses(), true)) {
            $approvalStatus = '';
        }

        $department = trim((string) $request->string('department'));
        $branch = trim((string) $request->string('branch'));
        $employeeId = trim((string) $request->string('employee_id'));

        if (! $viewer->can('attendance.view.all')) {
            $branch = '';
        }

        if ($viewer->can('attendance.view.department') && ! $viewer->can('attendance.view.all')) {
            $department = trim((string) ($viewer->profile?->department ?? ''));
        } elseif (! $viewer->can('attendance.view.department')) {
            $department = '';
        }

        if (! $viewer->can('attendance.view.all') && ! $viewer->can('attendance.view.department')) {
            $employeeId = '';
        }

        return [
            'status' => $status,
            'approval_status' => $approvalStatus,
            'attendance_date' => $attendanceDate,
            'use_date_range' => $useDateRange ? '1' : '0',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'range_mode' => 'absolute',
            'range_preset' => '',
            'department' => $department,
            'branch' => $branch,
            'employee_id' => ctype_digit($employeeId) ? $employeeId : '',
        ];
    }

    private function normalizeDateFilter(string $value, string $fallback = ''): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @param array<string, string> $filters
     */
    private function selectedFilterDate(array $filters): string
    {
        if (($filters['use_date_range'] ?? '0') === '1' && ($filters['date_from'] ?? '') !== '') {
            return (string) $filters['date_from'];
        }

        return (string) (($filters['attendance_date'] ?? '') !== '' ? $filters['attendance_date'] : now()->toDateString());
    }

    private function resolveViewerRole(User $viewer): string
    {
        if ($viewer->can('attendance.view.all') && $viewer->can('attendance.lock.month')) {
            return 'admin';
        }

        if ($viewer->can('attendance.view.all')) {
            return 'hr';
        }

        if ($viewer->can('attendance.view.department')) {
            return 'manager';
        }

        return 'employee';
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAttendancePayload(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('user_profiles', 'user_id')->where(fn ($query) => $query->where('is_employee', true))],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Attendance::statuses())],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveTargetUserId(User $viewer, array $validated): int
    {
        if ($viewer->can('attendance.edit') || $viewer->can('attendance.view.all') || $viewer->can('attendance.view.department')) {
            $candidate = (int) ($validated['user_id'] ?? 0);

            if ($candidate > 0) {
                return $candidate;
            }
        }

        return (int) $viewer->id;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?int}
     */
    private function normalizeTimes(string $attendanceDate, ?string $checkInTime, ?string $checkOutTime): array
    {
        $checkInAt = $this->parseDateTime($attendanceDate, $checkInTime);
        $checkOutAt = $this->parseDateTime($attendanceDate, $checkOutTime);

        if ($checkInAt === null || $checkOutAt === null) {
            return [$checkInAt, $checkOutAt, null];
        }

        return [$checkInAt, $checkOutAt, $checkInAt->diffInMinutes($checkOutAt)];
    }

    private function parseDateTime(string $attendanceDate, ?string $time): ?Carbon
    {
        if (blank($time)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', "{$attendanceDate} {$time}");
    }

    private function isMonthLockedForDate(string $date): bool
    {
        $month = Carbon::parse($date)->startOfMonth()->toDateString();

        return AttendanceMonthLock::query()
            ->whereDate('attendance_month', $month)
            ->whereNull('unlocked_at')
            ->exists();
    }

    private function mapAttendanceForResponse(Attendance $attendance, User $viewer, bool $isMonthLocked): array
    {
        $attendance->loadMissing(['user.profile', 'markedBy', 'approvedBy', 'rejectedBy', 'correctionRequestedBy']);

        $profile = $attendance->user?->profile;
        $status = (string) $attendance->status;
        $approvalStatus = $isMonthLocked ? Attendance::APPROVAL_LOCKED : (string) $attendance->approval_status;

        $checkInLabel = $attendance->check_in_at?->format('h:i A') ?? 'N/A';
        $checkOutLabel = $attendance->check_out_at?->format('h:i A') ?? 'N/A';
        $hoursLabel = $attendance->work_minutes !== null
            ? number_format($attendance->work_minutes / 60, 2).'h'
            : 'N/A';

        return [
            'id' => $attendance->id,
            'employee' => [
                'id' => $attendance->user?->id,
                'name' => $attendance->user?->name,
                'email' => $attendance->user?->email,
                'branch' => $profile?->branch,
                'department' => $profile?->department,
            ],
            'attendanceDate' => $attendance->attendance_date?->toDateString(),
            'attendanceDateLabel' => $attendance->attendance_date?->format('M d, Y'),
            'status' => $status,
            'statusLabel' => Str::headline(str_replace('_', ' ', $status)),
            'checkIn' => $checkInLabel,
            'checkOut' => $checkOutLabel,
            'totalHours' => $hoursLabel,
            'approvalStatus' => $approvalStatus,
            'approvalStatusLabel' => Str::headline(str_replace('_', ' ', $approvalStatus)),
            'approvalNote' => $attendance->approval_note,
            'correctionReason' => $attendance->correction_reason,
            'requestedCheckIn' => $attendance->requested_check_in_at?->format('h:i A'),
            'requestedCheckOut' => $attendance->requested_check_out_at?->format('h:i A'),
            'requestedCheckInIso' => $attendance->requested_check_in_at?->format('H:i'),
            'requestedCheckOutIso' => $attendance->requested_check_out_at?->format('H:i'),
            'requestedWorkMinutes' => $attendance->requested_work_minutes,
            'requestedBy' => $attendance->correctionRequestedBy?->name,
            'notes' => $attendance->notes,
            'isMonthLocked' => $isMonthLocked,
            'canApprove' => $viewer->can('attendance.approve')
                && ! $isMonthLocked
                && (string) $attendance->approval_status === Attendance::APPROVAL_PENDING
                && $this->canAccessAttendance($viewer, $attendance),
            'canReject' => $viewer->can('attendance.reject')
                && ! $isMonthLocked
                && (string) $attendance->approval_status === Attendance::APPROVAL_PENDING
                && $this->canAccessAttendance($viewer, $attendance),
            'canEdit' => $viewer->can('attendance.edit')
                && ! $isMonthLocked
                && $this->canAccessAttendance($viewer, $attendance),
            'canDelete' => $viewer->can('attendance.delete')
                && ! $isMonthLocked
                && $this->canAccessAttendance($viewer, $attendance),
            'canRequestCorrection' => $viewer->can('attendance.create')
                && ! $isMonthLocked
                && ! $this->isSelfOnlyViewer($viewer)
                && (int) $attendance->user_id === (int) $viewer->id,
            'isPendingCorrection' => $attendance->correction_requested_at !== null
                && (string) $attendance->approval_status === Attendance::APPROVAL_PENDING,
            'leftBorderClass' => $this->statusBorderClass($status),
        ];
    }

    /**
     * @return array{canPunchSelf:bool,nextAction:string,isComplete:bool,isMonthLocked:bool,todayDate:string,checkIn:string,checkOut:string}
     */
    private function resolvePunchState(User $viewer): array
    {
        $today = now()->toDateString();
        $isMonthLocked = $this->isMonthLockedForDate($today);
        $canPunchSelf = $viewer->can('attendance.create') && $this->isSelfOnlyViewer($viewer) && ! $isMonthLocked;

        $todayRecord = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $hasCheckIn = $todayRecord?->check_in_at !== null;
        $hasCheckOut = $todayRecord?->check_out_at !== null;

        $nextAction = 'none';
        if ($canPunchSelf) {
            if (! $hasCheckIn) {
                $nextAction = 'check_in';
            } elseif (! $hasCheckOut) {
                $nextAction = 'check_out';
            }
        }

        return [
            'canPunchSelf' => $canPunchSelf,
            'nextAction' => $nextAction,
            'isComplete' => $hasCheckIn && $hasCheckOut,
            'isMonthLocked' => $isMonthLocked,
            'todayDate' => $today,
            'checkIn' => $todayRecord?->check_in_at?->format('h:i A') ?? 'N/A',
            'checkOut' => $todayRecord?->check_out_at?->format('h:i A') ?? 'N/A',
        ];
    }

    private function statusBorderClass(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_PRESENT => 'border-emerald-500',
            Attendance::STATUS_ABSENT => 'border-red-500',
            Attendance::STATUS_LATE => 'border-amber-500',
            Attendance::STATUS_ON_LEAVE => 'border-blue-500',
            default => 'border-slate-400',
        };
    }

    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $metadata
     */
    private function logAudit(
        User $viewer,
        ?Attendance $attendance,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ): void {
        AuditLog::query()->create([
            'entity_type' => 'attendance',
            'entity_id' => $attendance?->id,
            'action' => $action,
            'performed_by_user_id' => $viewer->id,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'metadata' => $metadata === [] ? null : $metadata,
            'performed_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function actionSuccess(Request $request, string $message, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['message' => $message], $extra));
        }

        return redirect()
            ->route('modules.attendance.index')
            ->with('status', $message);
    }

    private function actionFailure(Request $request, string $message, int $status = Response::HTTP_BAD_REQUEST): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()
            ->route('modules.attendance.index')
            ->with('error', $message);
    }

    private function isSelfOnlyViewer(User $viewer): bool
    {
        return ! $viewer->can('attendance.view.department') && ! $viewer->can('attendance.view.all');
    }
}
