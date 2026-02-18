<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use App\Support\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
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
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validatePayload($request);
        [$checkInAt, $checkOutAt, $workMinutes] = $this->normalizeTimes(
            $validated['attendance_date'],
            $validated['check_in_time'] ?? null,
            $validated['check_out_time'] ?? null,
        );

        $attendance = Attendance::query()->updateOrCreate(
            [
                'user_id' => (int) $validated['user_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'work_minutes' => $workMinutes,
                'notes' => $validated['notes'] ?: null,
                'marked_by_user_id' => $viewer->id,
            ]
        );

        $attendance->loadMissing('user');
        $statusLabel = str((string) $attendance->status)->replace('_', ' ')->title();
        $actorName = $viewer->name;
        $employeeName = $attendance->user?->name ?? 'Unknown employee';
        $verb = $attendance->wasRecentlyCreated ? 'created' : 'updated';

        ActivityLogger::log(
            $viewer,
            'attendance.marked',
            "Attendance {$verb}: {$statusLabel}",
            "{$employeeName} • {$validated['attendance_date']} • by {$actorName}",
            '#0ea5e9',
            $attendance
        );

        return redirect()
            ->route('modules.attendance.index')
            ->with('status', 'Attendance saved successfully.');
    }

    public function edit(Request $request, Attendance $attendance): View
    {
        $this->ensureManagementAccess($request);

        $attendance->load(['user.profile', 'markedBy']);

        return view('modules.attendance.edit', [
            'attendance' => $attendance,
            'selectedEmployee' => $this->employeeAutocompleteSelection(
                (int) old('user_id', $attendance->user_id)
            ),
            'statusOptions' => Attendance::statuses(),
        ]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validatePayload($request);
        [$checkInAt, $checkOutAt, $workMinutes] = $this->normalizeTimes(
            $validated['attendance_date'],
            $validated['check_in_time'] ?? null,
            $validated['check_out_time'] ?? null,
        );

        $attendance->update([
            'user_id' => (int) $validated['user_id'],
            'attendance_date' => $validated['attendance_date'],
            'status' => $validated['status'],
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'work_minutes' => $workMinutes,
            'notes' => $validated['notes'] ?: null,
            'marked_by_user_id' => $viewer->id,
        ]);

        $attendance->loadMissing('user');
        $statusLabel = str((string) $attendance->status)->replace('_', ' ')->title();
        $employeeName = $attendance->user?->name ?? 'Unknown employee';

        ActivityLogger::log(
            $viewer,
            'attendance.updated',
            "Attendance updated: {$statusLabel}",
            "{$employeeName} • {$validated['attendance_date']}",
            '#0ea5e9',
            $attendance
        );

        return redirect()
            ->route('modules.attendance.index')
            ->with('status', 'Attendance record updated successfully.');
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $viewer = $request->user();

        if (! $viewer) {
            abort(403);
        }

        if (! $viewer->hasRole(UserRole::EMPLOYEE->value)) {
            abort(403, 'Only employees can check in.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->isHolidayDateForUser($viewer, now())) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'Today is configured as a holiday. Attendance check-in is disabled.');
        }

        $today = now()->toDateString();
        $record = Attendance::query()->firstOrNew([
            'user_id' => $viewer->id,
            'attendance_date' => $today,
        ]);

        if ($record->exists && $record->check_in_at !== null) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'You have already checked in today.');
        }

        $record->status = $record->status ?: Attendance::STATUS_PRESENT;
        $record->check_in_at = now();
        $record->marked_by_user_id = $viewer->id;
        $record->notes = $this->appendEmployeeActionNote(
            $record->notes,
            $validated['notes'] ?? null,
            'Check-in note'
        );

        if ($record->check_out_at !== null) {
            if ($record->check_out_at->lessThanOrEqualTo($record->check_in_at)) {
                $record->check_out_at = null;
                $record->work_minutes = null;
            } else {
                $record->work_minutes = $record->check_in_at->diffInMinutes($record->check_out_at);
            }
        }

        $record->save();

        ActivityLogger::log(
            $viewer,
            'attendance.check_in',
            'Attendance check-in',
            "{$viewer->name} checked in",
            '#0ea5e9',
            $record
        );

        return redirect()
            ->route('modules.attendance.index')
            ->with('status', 'Checked in successfully.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $viewer = $request->user();

        if (! $viewer) {
            abort(403);
        }

        if (! $viewer->hasRole(UserRole::EMPLOYEE->value)) {
            abort(403, 'Only employees can check out.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->isHolidayDateForUser($viewer, now())) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'Today is configured as a holiday. Attendance check-out is disabled.');
        }

        $record = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        if (! $record || $record->check_in_at === null) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'Please check in before checking out.');
        }

        if ($record->check_out_at !== null) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'You have already checked out today.');
        }

        $checkOutAt = now();
        if ($checkOutAt->lessThanOrEqualTo($record->check_in_at)) {
            return redirect()
                ->route('modules.attendance.index')
                ->with('error', 'Invalid check-out time.');
        }

        $record->check_out_at = $checkOutAt;
        $record->work_minutes = $record->check_in_at->diffInMinutes($checkOutAt);
        if (blank($record->status) || $record->status === Attendance::STATUS_ABSENT) {
            $record->status = Attendance::STATUS_PRESENT;
        }
        $record->marked_by_user_id = $viewer->id;
        $record->notes = $this->appendEmployeeActionNote(
            $record->notes,
            $validated['notes'] ?? null,
            'Check-out note'
        );
        $record->save();

        ActivityLogger::log(
            $viewer,
            'attendance.check_out',
            'Attendance check-out',
            "{$viewer->name} checked out",
            '#0ea5e9',
            $record
        );

        return redirect()
            ->route('modules.attendance.index')
            ->with('status', 'Checked out successfully.');
    }

    private function managementPage(Request $request): View
    {
        $search = (string) $request->string('q');
        $status = (string) $request->string('status');
        $department = (string) $request->string('department');
        $branch = (string) $request->string('branch');
        $attendanceDate = (string) $request->string('attendance_date');
        $employeeId = (int) $request->integer('employee_id');

        $statusOptions = Attendance::statuses();

        $records = Attendance::query()
            ->with(['user.profile', 'markedBy'])
            ->whereHas('user', function (Builder $query): void {
                $query->workforce();
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('user', function (Builder $userQuery) use ($search): void {
                    $userQuery
                        ->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($search): void {
                                    $profileQuery
                                        ->where('department', 'like', "%{$search}%")
                                        ->orWhere('branch', 'like', "%{$search}%")
                                        ->orWhere('job_title', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($attendanceDate !== '', function (Builder $query) use ($attendanceDate): void {
                $query->whereDate('attendance_date', $attendanceDate);
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
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $employeeIds = User::query()
            ->workforce()
            ->pluck('id');

        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $monthStartCarbon = $now->copy()->startOfMonth();
        $monthEndCarbon = $now->copy()->endOfMonth();
        $monthStart = $monthStartCarbon->toDateString();
        $monthEnd = $monthEndCarbon->toDateString();
        $previousMonthEnd = $monthStartCarbon->copy()->subSecond();

        $baseTodayQuery = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $today);

        $baseYesterdayQuery = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $yesterday);

        $totalEmployees = $employeeIds->count();
        $totalEmployeesLastMonth = User::query()
            ->workforce()
            ->where('created_at', '<=', $previousMonthEnd)
            ->count();
        $markedToday = (clone $baseTodayQuery)->count();
        $markedYesterday = (clone $baseYesterdayQuery)->count();
        $presentToday = (clone $baseTodayQuery)
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_REMOTE,
            ])
            ->count();
        $presentYesterday = (clone $baseYesterdayQuery)
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_REMOTE,
            ])
            ->count();
        $absentToday = (clone $baseTodayQuery)
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();
        $absentYesterday = (clone $baseYesterdayQuery)
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();
        $pendingToday = max(0, $totalEmployees - $markedToday);
        $pendingYesterday = max(0, $totalEmployees - $markedYesterday);
        $coverageToday = $totalEmployees > 0 ? round(($markedToday / $totalEmployees) * 100, 1) : 0.0;
        $coverageYesterday = $totalEmployees > 0 ? round(($markedYesterday / $totalEmployees) * 100, 1) : 0.0;
        $presentShareToday = $markedToday > 0 ? round(($presentToday / $markedToday) * 100, 1) : 0.0;
        $presentShareYesterday = $markedYesterday > 0 ? round(($presentYesterday / $markedYesterday) * 100, 1) : 0.0;

        $monthlyBaseQuery = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd]);

        $statusBreakdown = (clone $monthlyBaseQuery)
            ->selectRaw('status, COUNT(*) as record_count')
            ->groupBy('status')
            ->orderByDesc('record_count')
            ->get();

        return view('modules.attendance.admin', [
            'records' => $records,
            'selectedCreateEmployee' => $this->employeeAutocompleteSelection((int) old('user_id')),
            'selectedFilterEmployee' => $this->employeeAutocompleteSelection($employeeId > 0 ? $employeeId : null),
            'departmentOptions' => $this->departmentOptions($employeeIds),
            'branchOptions' => $this->branchOptions($employeeIds),
            'statusOptions' => $statusOptions,
            'statusBreakdown' => $statusBreakdown,
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'markedToday' => $markedToday,
                'presentToday' => $presentToday,
                'absentToday' => $absentToday,
                'pendingToday' => $pendingToday,
                'recordsThisMonth' => (clone $monthlyBaseQuery)->count(),
            ],
            'statTrends' => [
                'headcountDelta' => $totalEmployees - $totalEmployeesLastMonth,
                'markedDelta' => $markedToday - $markedYesterday,
                'presentDelta' => $presentToday - $presentYesterday,
                'absentDelta' => $absentToday - $absentYesterday,
                'pendingDelta' => $pendingToday - $pendingYesterday,
                'coverageToday' => $coverageToday,
                'coverageYesterday' => $coverageYesterday,
                'presentShareToday' => $presentShareToday,
                'presentShareYesterday' => $presentShareYesterday,
                'markedYesterday' => $markedYesterday,
                'presentYesterday' => $presentYesterday,
                'pendingYesterday' => $pendingYesterday,
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
                'department' => $department,
                'branch' => $branch,
                'attendance_date' => $attendanceDate,
                'employee_id' => $employeeId > 0 ? (string) $employeeId : '',
            ],
        ]);
    }

    private function employeePage(Request $request, User $viewer): View
    {
        $status = (string) $request->string('status');
        $month = (string) $request->string('month');
        $statusOptions = Attendance::statuses();
        $resolvedMonth = now()->startOfMonth();
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            $resolvedMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        $rangeStart = $resolvedMonth->copy()->startOfMonth()->toDateString();
        $rangeEnd = $resolvedMonth->copy()->endOfMonth()->toDateString();

        $records = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereBetween('attendance_date', [$rangeStart, $rangeEnd])
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('attendance_date')
            ->paginate(12)
            ->withQueryString();

        $monthRecords = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereBetween('attendance_date', [$rangeStart, $rangeEnd])
            ->get(['status', 'work_minutes']);

        $todayRecord = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        $presentCount = $monthRecords
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_HALF_DAY, Attendance::STATUS_REMOTE])
            ->count();

        $absentCount = $monthRecords
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();

        $totalWorkedMinutes = (int) $monthRecords->sum(fn (Attendance $record): int => (int) ($record->work_minutes ?? 0));
        $averageDailyHours = $presentCount > 0
            ? round(($totalWorkedMinutes / $presentCount) / 60, 1)
            : 0;

        return view('modules.attendance.employee', [
            'viewer' => $viewer,
            'todayRecord' => $todayRecord,
            'records' => $records,
            'statusOptions' => $statusOptions,
            'stats' => [
                'monthRecords' => $monthRecords->count(),
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'averageDailyHours' => $averageDailyHours,
                'totalWorkedHours' => round($totalWorkedMinutes / 60, 1),
            ],
            'filters' => [
                'status' => $status,
                'month' => $resolvedMonth->format('Y-m'),
            ],
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Attendance::statuses())],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
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

    private function appendEmployeeActionNote(?string $existingNotes, mixed $newNote, string $label): ?string
    {
        $newText = trim((string) $newNote);
        if ($newText === '') {
            return blank($existingNotes) ? null : (string) $existingNotes;
        }

        $prefixed = "{$label}: {$newText}";
        $current = trim((string) $existingNotes);

        if ($current === '') {
            return $prefixed;
        }

        return "{$current}\n{$prefixed}";
    }

    private function isHolidayDateForUser(User $user, Carbon $date): bool
    {
        $user->loadMissing('profile');
        $holidayMap = HolidayCalendar::dateMap(
            $date->copy()->startOfDay(),
            $date->copy()->startOfDay(),
            $user->profile?->branch,
            false
        );

        return isset($holidayMap[$date->toDateString()]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function employeeAutocompleteSelection(?int $employeeId): ?array
    {
        if (($employeeId ?? 0) <= 0) {
            return null;
        }

        $employee = User::query()
            ->workforce()
            ->with('profile:user_id,department')
            ->whereKey((int) $employeeId)
            ->first();

        if (! $employee instanceof User) {
            return null;
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'department' => $employee->profile?->department ?? '',
            'employee_code' => $employee->profile?->employee_code ?: User::makeEmployeeCode($employee->id),
        ];
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
