<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $viewer = $request->user();

        if (! $viewer instanceof User) {
            abort(403);
        }

        $isManagement = $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ]);
        $reportTypeOptions = $this->reportTypeOptions();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'report_type' => ['nullable', Rule::in(array_keys($reportTypeOptions))],
            'month' => ['nullable', 'date_format:Y-m'],
            'department' => ['nullable', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:120'],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);

        $reportType = (string) ($validated['report_type'] ?? 'comprehensive');
        $monthlyCursor = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', (string) $validated['month'])->startOfMonth()
            : now()->startOfMonth();
        $from = $monthlyCursor->copy()->startOfMonth()->startOfDay();
        $to = $monthlyCursor->copy()->endOfMonth()->endOfDay();

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'report_type' => $reportType,
            'month' => $monthlyCursor->format('Y-m'),
            'department' => trim((string) ($validated['department'] ?? '')),
            'branch' => trim((string) ($validated['branch'] ?? '')),
            'employee_id' => (int) ($validated['employee_id'] ?? 0),
            'export' => (string) ($validated['export'] ?? ''),
        ];
        $reportTypeLabel = $reportTypeOptions[$reportType] ?? $reportTypeOptions['comprehensive'];
        $visibleSections = $this->sectionsForReportType($reportType);

        $employeeScopeQuery = User::query()
            ->workforce()
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('email', 'like', "%{$filters['q']}%");
                });
            });

        if ($isManagement) {
            if ($filters['employee_id'] > 0) {
                $employeeScopeQuery->whereKey($filters['employee_id']);
            }

            if ($filters['department'] !== '') {
                $employeeScopeQuery->whereHas('profile', function (Builder $query) use ($filters): void {
                    $query->where('department', $filters['department']);
                });
            }

            if ($filters['branch'] !== '') {
                $employeeScopeQuery->whereHas('profile', function (Builder $query) use ($filters): void {
                    $query->where('branch', $filters['branch']);
                });
            }
        } else {
            $employeeScopeQuery->whereKey($viewer->id);
        }

        $employeeCount = (clone $employeeScopeQuery)->count('users.id');
        $employeeIdSubquery = (clone $employeeScopeQuery)->select('users.id');
        $periodDays = max(
            1,
            (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1
        );

        $attendanceBase = Attendance::query()
            ->whereIn('user_id', (clone $employeeIdSubquery))
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);

        $attendanceTotals = (clone $attendanceBase)
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('present', 'remote') THEN 1 WHEN status = 'half_day' THEN 0.5 ELSE 0 END), 0) as present_units")
            ->selectRaw('COALESCE(SUM(work_minutes), 0) as work_minutes_total')
            ->first();

        $attendanceStatusBreakdown = (clone $attendanceBase)
            ->selectRaw('status, COUNT(*) as record_count')
            ->groupBy('status')
            ->orderByDesc('record_count')
            ->get();

        $leaveBase = LeaveRequest::query()
            ->whereIn('user_id', (clone $employeeIdSubquery))
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereDate('start_date', '<=', $to->toDateString())
                    ->whereDate('end_date', '>=', $from->toDateString());
            });
        $leaveRows = (clone $leaveBase)
            ->get(['user_id', 'leave_type', 'status', 'start_date', 'end_date', 'total_days']);
        $leaveSummary = $this->summarizeLeaveRows($leaveRows, $from, $to);

        $payrollEnabled = Schema::hasTable('payrolls');
        $payrollStatusBreakdown = collect();
        $payrollTotals = (object) [
            'generated_entries' => 0,
            'paid_entries' => 0,
            'net_salary' => 0.0,
            'total_deductions' => 0.0,
        ];

        if ($payrollEnabled) {
            $payrollRangeStart = $from->copy()->startOfMonth()->toDateString();
            $payrollRangeEnd = $to->copy()->startOfMonth()->toDateString();

            $payrollBase = Payroll::query()
                ->whereIn('user_id', (clone $employeeIdSubquery))
                ->whereBetween('payroll_month', [$payrollRangeStart, $payrollRangeEnd]);

            $payrollTotals = (clone $payrollBase)
                ->selectRaw('COUNT(*) as generated_entries')
                ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_entries")
                ->selectRaw('COALESCE(SUM(net_salary), 0) as net_salary')
                ->selectRaw('COALESCE(SUM(total_deductions), 0) as total_deductions')
                ->first();

            $payrollStatusBreakdown = (clone $payrollBase)
                ->selectRaw('status, COUNT(*) as entry_count')
                ->groupBy('status')
                ->orderByDesc('entry_count')
                ->get();
        }

        $attendancePresentUnits = (float) ($attendanceTotals?->present_units ?? 0);
        $attendanceTotalRecords = (int) ($attendanceTotals?->total_records ?? 0);
        $attendanceRate = ($employeeCount > 0 && $periodDays > 0)
            ? round(($attendancePresentUnits / ($employeeCount * $periodDays)) * 100, 1)
            : 0.0;

        $stats = [
            'employeeCount' => $employeeCount,
            'periodDays' => $periodDays,
            'attendanceRecords' => $attendanceTotalRecords,
            'attendancePresentUnits' => $attendancePresentUnits,
            'attendanceRate' => $attendanceRate,
            'attendanceWorkHours' => round(((float) ($attendanceTotals?->work_minutes_total ?? 0)) / 60, 1),
            'leaveRequests' => (int) ($leaveSummary['total_requests'] ?? 0),
            'leaveApprovedDays' => round((float) ($leaveSummary['approved_days'] ?? 0), 1),
            'leavePendingRequests' => (int) ($leaveSummary['pending_requests'] ?? 0),
            'payrollEntries' => (int) ($payrollTotals?->generated_entries ?? 0),
            'payrollPaidEntries' => (int) ($payrollTotals?->paid_entries ?? 0),
            'payrollNet' => round((float) ($payrollTotals?->net_salary ?? 0), 2),
            'payrollDeductions' => round((float) ($payrollTotals?->total_deductions ?? 0), 2),
        ];

        if ($filters['export'] === 'csv') {
            $employeeExportQuery = (clone $employeeScopeQuery)
                ->with('profile');

            ActivityLogger::log(
                $viewer,
                'reports.exported',
                'Report exported',
                "{$reportTypeLabel} • {$from->toDateString()} to {$to->toDateString()}",
                '#7c3aed',
                null,
                [
                    'employee_count' => $employeeCount,
                    'report_type' => $reportType,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ]
            );

            return $this->exportCsv(
                $employeeExportQuery,
                $from,
                $to,
                $reportType,
                $reportTypeLabel,
                $payrollEnabled
            );
        }

        $employees = (clone $employeeScopeQuery)
            ->with('profile')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $employeeSummaryRows = collect();
        if (in_array('employee_summary', $visibleSections, true)) {
            $employeeSummaryRows = $this->buildEmployeeSummaryRows(
                collect($employees->items()),
                $from,
                $to,
                $payrollEnabled
            );
        }

        $departmentOptions = collect();
        $branchOptions = collect();
        if ($isManagement) {
            $departmentOptions = UserProfile::query()
                ->whereHas('user', function (Builder $query): void {
                    $query->workforce();
                })
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->pluck('department')
                ->map(fn ($value): string => trim((string) $value))
                ->unique()
                ->sort()
                ->values();

            $branchOptions = UserProfile::query()
                ->whereHas('user', function (Builder $query): void {
                    $query->workforce();
                })
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->pluck('branch')
                ->map(fn ($value): string => trim((string) $value))
                ->unique()
                ->sort()
                ->values();
        }

        return view('modules.reports.index', [
            'isManagement' => $isManagement,
            'filters' => $filters,
            'stats' => $stats,
            'periodLabel' => $monthlyCursor->format('F Y'),
            'reportTypeLabel' => $reportTypeLabel,
            'reportTypeOptions' => $reportTypeOptions,
            'visibleSections' => $visibleSections,
            'employees' => $employees,
            'rows' => $employeeSummaryRows,
            'attendanceStatusBreakdown' => $attendanceStatusBreakdown,
            'leaveStatusBreakdown' => $leaveSummary['status_breakdown'],
            'leaveTypeBreakdown' => $leaveSummary['type_breakdown'],
            'payrollStatusBreakdown' => $payrollStatusBreakdown,
            'payrollEnabled' => $payrollEnabled,
            'departmentOptions' => $departmentOptions,
            'branchOptions' => $branchOptions,
            'selectedReportEmployee' => $this->employeeAutocompleteSelection($filters['employee_id']),
        ]);
    }

    public function activity(Request $request): View
    {
        $viewer = $request->user();

        if (! $viewer instanceof User) {
            abort(403);
        }

        $isManagement = $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ]);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'q' => ['nullable', 'string', 'max:150'],
            'event_key' => ['nullable', 'string', 'max:100'],
            'actor_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $defaultFrom = now()->startOfMonth();
        $defaultTo = now()->endOfMonth();

        $fromDateInput = trim((string) ($validated['from_date'] ?? ''));
        $toDateInput = trim((string) ($validated['to_date'] ?? ''));

        $resolvedFrom = $fromDateInput !== ''
            ? Carbon::parse($fromDateInput)->startOfDay()
            : ($toDateInput !== '' ? Carbon::parse($toDateInput)->startOfDay() : $defaultFrom->copy()->startOfDay());
        $resolvedTo = $toDateInput !== ''
            ? Carbon::parse($toDateInput)->endOfDay()
            : ($fromDateInput !== '' ? Carbon::parse($fromDateInput)->endOfDay() : $defaultTo->copy()->endOfDay());

        $from = $resolvedFrom->copy();
        $to = $resolvedTo->copy();

        $filters = [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'q' => trim((string) ($validated['q'] ?? '')),
            'event_key' => trim((string) ($validated['event_key'] ?? '')),
            'actor_user_id' => (int) ($validated['actor_user_id'] ?? 0),
        ];

        $eventKeyOptions = collect();
        $actorOptions = collect();
        $activities = new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: 20,
            currentPage: 1,
            options: ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'total' => 0,
            'uniqueActors' => 0,
            'systemEvents' => 0,
        ];

        if (Schema::hasTable('activities')) {
            $optionsQuery = Activity::query()
                ->whereBetween('occurred_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

            if (! $isManagement) {
                $optionsQuery->where('actor_user_id', $viewer->id);
            }

            $eventKeyOptions = (clone $optionsQuery)
                ->whereNotNull('event_key')
                ->where('event_key', '!=', '')
                ->select('event_key')
                ->distinct()
                ->orderBy('event_key')
                ->pluck('event_key');

            if ($isManagement) {
                $actorIds = (clone $optionsQuery)
                    ->whereNotNull('actor_user_id')
                    ->select('actor_user_id')
                    ->distinct()
                    ->pluck('actor_user_id')
                    ->map(fn ($value): int => (int) $value)
                    ->filter(fn (int $value): bool => $value > 0)
                    ->values();

                $actorOptions = User::query()
                    ->whereIn('id', $actorIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);
            }

            $activityQuery = Activity::query()
                ->with('actor')
                ->whereBetween('occurred_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

            if (! $isManagement) {
                $activityQuery->where('actor_user_id', $viewer->id);
            }

            if ($filters['q'] !== '') {
                $activityQuery->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('title', 'like', "%{$filters['q']}%")
                        ->orWhere('meta', 'like', "%{$filters['q']}%")
                        ->orWhere('event_key', 'like', "%{$filters['q']}%")
                        ->orWhereHas('actor', function (Builder $actorQuery) use ($filters): void {
                            $actorQuery->where('name', 'like', "%{$filters['q']}%");
                        });
                });
            }

            if ($filters['event_key'] !== '') {
                $activityQuery->where('event_key', $filters['event_key']);
            }

            if ($isManagement && $filters['actor_user_id'] > 0) {
                $activityQuery->where('actor_user_id', $filters['actor_user_id']);
            }

            $statsRow = (clone $activityQuery)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COUNT(DISTINCT actor_user_id) as unique_actors')
                ->selectRaw('SUM(CASE WHEN actor_user_id IS NULL THEN 1 ELSE 0 END) as system_events')
                ->first();
            $stats['total'] = (int) ($statsRow?->total ?? 0);
            $stats['uniqueActors'] = (int) ($statsRow?->unique_actors ?? 0);
            $stats['systemEvents'] = (int) ($statsRow?->system_events ?? 0);

            $activities = (clone $activityQuery)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('modules.reports.activity', [
            'filters' => $filters,
            'stats' => $stats,
            'activities' => $activities,
            'eventKeyOptions' => $eventKeyOptions,
            'actorOptions' => $actorOptions,
            'isManagement' => $isManagement,
            'periodLabel' => $from->format('M d, Y').' - '.$to->format('M d, Y'),
        ]);
    }

    public function activityShow(Request $request, int $activity): View
    {
        $viewer = $request->user();

        if (! $viewer instanceof User) {
            abort(403);
        }

        if (! Schema::hasTable('activities')) {
            abort(404);
        }

        $activityModel = Activity::query()->findOrFail($activity);

        $isManagement = $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ]);

        if (! $isManagement && (int) $activityModel->actor_user_id !== (int) $viewer->id) {
            abort(403);
        }

        $activityModel->loadMissing(['actor', 'subject']);

        $payload = ActivityLogger::sanitizePayloadForDisplay($activityModel->payload);
        $payloadJson = '{}';
        if ($payload !== []) {
            $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (is_string($encodedPayload) && $encodedPayload !== '') {
                $payloadJson = $encodedPayload;
            }
        }

        $backFilters = $request->only([
            'from_date',
            'to_date',
            'q',
            'event_key',
            'actor_user_id',
            'page',
        ]);
        $backFilters = array_filter($backFilters, fn ($value): bool => filled($value));
        $backUrl = route('modules.reports.activity', $backFilters);

        return view('modules.reports.activity-show', [
            'activity' => $activityModel,
            'backUrl' => $backUrl,
            'payloadJson' => $payloadJson,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function employeeAutocompleteSelection(int $employeeId): ?array
    {
        if ($employeeId <= 0) {
            return null;
        }

        $employee = User::query()
            ->workforce()
            ->with('profile:user_id,department')
            ->whereKey($employeeId)
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
     * @param Collection<int, User> $employees
     * @return Collection<int, array<string, int|float|string>>
     */
    private function buildEmployeeSummaryRows(
        Collection $employees,
        Carbon $from,
        Carbon $to,
        bool $payrollEnabled
    ): Collection {
        $ids = $employees->pluck('id')->map(fn ($id): int => (int) $id)->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $attendanceByUser = Attendance::query()
            ->whereIn('user_id', $ids)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->select('user_id')
            ->selectRaw('COUNT(*) as marked_days')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('present', 'remote') THEN 1 WHEN status = 'half_day' THEN 0.5 ELSE 0 END), 0) as present_units")
            ->selectRaw('COALESCE(SUM(work_minutes), 0) as work_minutes')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $leaveRows = LeaveRequest::query()
            ->whereIn('user_id', $ids)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['user_id', 'leave_type', 'status', 'start_date', 'end_date', 'total_days']);
        $leaveSummary = $this->summarizeLeaveRows($leaveRows, $from, $to);
        $leaveByUser = $leaveSummary['by_user'];

        $payrollByUser = collect();
        $latestPayrollByUser = collect();
        if ($payrollEnabled) {
            $payrollRangeStart = $from->copy()->startOfMonth()->toDateString();
            $payrollRangeEnd = $to->copy()->startOfMonth()->toDateString();

            $payrollByUser = Payroll::query()
                ->whereIn('user_id', $ids)
                ->whereBetween('payroll_month', [$payrollRangeStart, $payrollRangeEnd])
                ->select('user_id')
                ->selectRaw('COUNT(*) as payroll_entries')
                ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_entries")
                ->selectRaw('COALESCE(SUM(net_salary), 0) as net_salary')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $latestPayrollByUser = Payroll::query()
                ->whereIn('user_id', $ids)
                ->whereBetween('payroll_month', [$payrollRangeStart, $payrollRangeEnd])
                ->orderByDesc('payroll_month')
                ->orderByDesc('id')
                ->get(['user_id', 'payroll_month', 'status'])
                ->groupBy('user_id')
                ->map(fn (Collection $rows) => $rows->first());
        }

        return $employees
            ->map(function (User $employee) use ($attendanceByUser, $leaveByUser, $payrollByUser, $latestPayrollByUser): array {
                $profile = $employee->profile;
                $userId = (int) $employee->id;

                $attendance = $attendanceByUser->get($userId);
                $markedDays = (int) ($attendance?->marked_days ?? 0);
                $presentUnits = (float) ($attendance?->present_units ?? 0);
                $workMinutes = (float) ($attendance?->work_minutes ?? 0);

                $leave = $leaveByUser->get($userId);
                $payroll = $payrollByUser->get($userId);
                $latestPayroll = $latestPayrollByUser->get($userId);

                $latestMonth = $latestPayroll?->payroll_month instanceof Carbon
                    ? $latestPayroll->payroll_month->format('M Y')
                    : 'N/A';

                return [
                    'name' => (string) $employee->name,
                    'email' => (string) $employee->email,
                    'department' => (string) ($profile?->department ?? 'Unassigned'),
                    'branch' => (string) ($profile?->branch ?? 'Unassigned'),
                    'marked_days' => $markedDays,
                    'present_units' => round($presentUnits, 1),
                    'attendance_percent' => $markedDays > 0 ? round(($presentUnits / $markedDays) * 100, 1) : 0.0,
                    'avg_hours' => $markedDays > 0 ? round(($workMinutes / 60) / $markedDays, 2) : 0.0,
                    'approved_leave_days' => round((float) ($leave?->approved_days ?? 0), 1),
                    'pending_leave_requests' => (int) ($leave?->pending_requests ?? 0),
                    'payroll_entries' => (int) ($payroll?->payroll_entries ?? 0),
                    'paid_entries' => (int) ($payroll?->paid_entries ?? 0),
                    'net_salary' => round((float) ($payroll?->net_salary ?? 0), 2),
                    'latest_payroll_month' => $latestMonth,
                    'latest_payroll_status' => $latestPayroll?->status
                        ? (string) str((string) $latestPayroll->status)->replace('_', ' ')->title()
                        : 'N/A',
                ];
            })
            ->values();
    }

    private function exportCsv(
        Builder $employeeQuery,
        Carbon $from,
        Carbon $to,
        string $reportType,
        string $reportTypeLabel,
        bool $payrollEnabled
    ): StreamedResponse {
        [$headers, $resolver] = $this->csvColumnsForReportType($reportType);

        $filename = sprintf(
            'hr-%s-report-%s-to-%s.csv',
            str($reportType)->replace('_', '-'),
            $from->format('Ymd'),
            $to->format('Ymd')
        );

        return response()->streamDownload(function () use ($employeeQuery, $from, $to, $headers, $resolver, $reportTypeLabel, $payrollEnabled): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $this->sanitizeCsvRow(['Report Type', $reportTypeLabel]));
            fputcsv($handle, $this->sanitizeCsvRow(['Report Period', $from->toDateString().' to '.$to->toDateString()]));
            fputcsv($handle, []);
            fputcsv($handle, $this->sanitizeCsvRow($headers));

            $employeeQuery->chunkById(300, function (Collection $employees) use ($from, $to, $payrollEnabled, $resolver, $handle): void {
                $employees->loadMissing('profile');

                $rows = $this->buildEmployeeSummaryRows(
                    $employees,
                    $from,
                    $to,
                    $payrollEnabled
                );

                foreach ($rows as $row) {
                    fputcsv($handle, $this->sanitizeCsvRow($resolver($row)));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param Collection<int, LeaveRequest> $leaveRows
     * @return array{
     *     total_requests: int,
     *     approved_days: float,
     *     pending_requests: int,
     *     status_breakdown: Collection<int, object{status: string, request_count: int}>,
     *     type_breakdown: Collection<int, object{leave_type: string, request_count: int, approved_days: float}>,
     *     by_user: Collection<int, object{approved_days: float, pending_requests: int}>
     * }
     */
    private function summarizeLeaveRows(Collection $leaveRows, Carbon $from, Carbon $to): array
    {
        $statusCounts = [];
        $typeCounts = [];
        $approvedByType = [];
        $byUser = [];
        $pendingRequests = 0;
        $approvedDays = 0.0;

        foreach ($leaveRows as $leave) {
            $status = trim((string) ($leave->status ?? ''));
            $leaveType = trim((string) ($leave->leave_type ?? ''));
            $userId = (int) ($leave->user_id ?? 0);

            if ($status !== '') {
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            }

            if ($leaveType !== '') {
                $typeCounts[$leaveType] = ($typeCounts[$leaveType] ?? 0) + 1;
            }

            if ($status === LeaveRequest::STATUS_PENDING) {
                $pendingRequests++;

                if ($userId > 0) {
                    if (! array_key_exists($userId, $byUser)) {
                        $byUser[$userId] = [
                            'approved_days' => 0.0,
                            'pending_requests' => 0,
                        ];
                    }
                    $byUser[$userId]['pending_requests']++;
                }
            }

            if ($status !== LeaveRequest::STATUS_APPROVED) {
                continue;
            }

            $overlapDays = $this->overlapLeaveUnits(
                $leave->start_date,
                $leave->end_date,
                (float) ($leave->total_days ?? 0),
                $from,
                $to
            );
            if ($overlapDays <= 0) {
                continue;
            }

            $approvedDays += $overlapDays;

            if ($leaveType !== '') {
                $approvedByType[$leaveType] = ($approvedByType[$leaveType] ?? 0) + $overlapDays;
            }

            if ($userId > 0) {
                if (! array_key_exists($userId, $byUser)) {
                    $byUser[$userId] = [
                        'approved_days' => 0.0,
                        'pending_requests' => 0,
                    ];
                }
                $byUser[$userId]['approved_days'] += $overlapDays;
            }
        }

        $statusBreakdown = collect($statusCounts)
            ->map(fn (int $count, string $status): object => (object) [
                'status' => $status,
                'request_count' => $count,
            ])
            ->sortByDesc(fn (object $item): int => (int) $item->request_count)
            ->values();

        $typeBreakdown = collect($typeCounts)
            ->map(function (int $count, string $leaveType) use ($approvedByType): object {
                return (object) [
                    'leave_type' => $leaveType,
                    'request_count' => $count,
                    'approved_days' => (float) ($approvedByType[$leaveType] ?? 0.0),
                ];
            })
            ->sortByDesc(fn (object $item): int => (int) $item->request_count)
            ->values();

        $byUserCollection = collect($byUser)->map(function (array $totals): object {
            return (object) [
                'approved_days' => (float) ($totals['approved_days'] ?? 0.0),
                'pending_requests' => (int) ($totals['pending_requests'] ?? 0),
            ];
        });

        return [
            'total_requests' => $leaveRows->count(),
            'approved_days' => $approvedDays,
            'pending_requests' => $pendingRequests,
            'status_breakdown' => $statusBreakdown,
            'type_breakdown' => $typeBreakdown,
            'by_user' => $byUserCollection,
        ];
    }

    private function overlapLeaveUnits(
        mixed $startDate,
        mixed $endDate,
        float $totalDays,
        Carbon $from,
        Carbon $to
    ): float {
        if ($totalDays <= 0) {
            return 0.0;
        }

        try {
            $start = $startDate instanceof Carbon
                ? $startDate->copy()->startOfDay()
                : Carbon::parse((string) $startDate)->startOfDay();
            $end = $endDate instanceof Carbon
                ? $endDate->copy()->endOfDay()
                : Carbon::parse((string) $endDate)->endOfDay();
        } catch (Throwable) {
            return 0.0;
        }

        if ($start->greaterThan($end)) {
            return 0.0;
        }

        $windowStart = $start->greaterThan($from) ? $start : $from->copy()->startOfDay();
        $windowEnd = $end->lessThan($to) ? $end : $to->copy()->endOfDay();

        if ($windowStart->greaterThan($windowEnd)) {
            return 0.0;
        }

        $requestDays = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $overlapDays = max(0, (int) $windowStart->copy()->startOfDay()->diffInDays($windowEnd->copy()->startOfDay()) + 1);
        $units = $overlapDays * ($totalDays / $requestDays);
        $units = min($totalDays, max(0, $units));

        return round($units, 4);
    }

    /**
     * @param list<int|float|string> $values
     * @return list<int|float|string>
     */
    private function sanitizeCsvRow(array $values): array
    {
        return array_map(function (int|float|string $value): int|float|string {
            if (! is_string($value) || $value === '') {
                return $value;
            }

            $firstChar = $value[0];
            $trimmedLeading = ltrim($value);
            $firstTrimmedChar = $trimmedLeading !== '' ? $trimmedLeading[0] : $firstChar;

            if (
                in_array($firstChar, ["\t", "\r", "\n"], true)
                || in_array($firstTrimmedChar, ['=', '+', '-', '@'], true)
            ) {
                return "'".$value;
            }

            return $value;
        }, $values);
    }

    /**
     * @return array<string, string>
     */
    private function reportTypeOptions(): array
    {
        return [
            'comprehensive' => 'Comprehensive',
            'attendance_monthly' => 'Monthly Attendance',
            'leave_monthly' => 'Monthly Leave',
            'payroll_monthly' => 'Monthly Payroll',
        ];
    }

    /**
     * @return list<string>
     */
    private function sectionsForReportType(string $reportType): array
    {
        return match ($reportType) {
            'attendance_monthly' => ['attendance', 'employee_summary'],
            'leave_monthly' => ['leave', 'employee_summary'],
            'payroll_monthly' => ['payroll', 'employee_summary'],
            default => ['attendance', 'leave', 'payroll', 'employee_summary'],
        };
    }

    /**
     * @return array{0: list<string>, 1: \Closure(array<string, int|float|string>): list<int|float|string>}
     */
    private function csvColumnsForReportType(string $reportType): array
    {
        if ($reportType === 'attendance_monthly') {
            return [
                ['Employee', 'Email', 'Department', 'Branch', 'Marked Days', 'Present Units', 'Attendance (%)', 'Avg Hours/Day'],
                fn (array $row): array => [
                    $row['name'],
                    $row['email'],
                    $row['department'],
                    $row['branch'],
                    $row['marked_days'],
                    $row['present_units'],
                    $row['attendance_percent'],
                    $row['avg_hours'],
                ],
            ];
        }

        if ($reportType === 'leave_monthly') {
            return [
                ['Employee', 'Email', 'Department', 'Branch', 'Approved Leave Days', 'Pending Leave Requests'],
                fn (array $row): array => [
                    $row['name'],
                    $row['email'],
                    $row['department'],
                    $row['branch'],
                    $row['approved_leave_days'],
                    $row['pending_leave_requests'],
                ],
            ];
        }

        if ($reportType === 'payroll_monthly') {
            return [
                ['Employee', 'Email', 'Department', 'Branch', 'Payroll Entries', 'Paid Entries', 'Net Salary', 'Latest Payroll Month', 'Latest Payroll Status'],
                fn (array $row): array => [
                    $row['name'],
                    $row['email'],
                    $row['department'],
                    $row['branch'],
                    $row['payroll_entries'],
                    $row['paid_entries'],
                    $row['net_salary'],
                    $row['latest_payroll_month'],
                    $row['latest_payroll_status'],
                ],
            ];
        }

        return [
            [
                'Employee',
                'Email',
                'Department',
                'Branch',
                'Marked Days',
                'Present Units',
                'Attendance (%)',
                'Avg Hours/Day',
                'Approved Leave Days',
                'Pending Leave Requests',
                'Payroll Entries',
                'Paid Payroll Entries',
                'Net Salary',
                'Latest Payroll Month',
                'Latest Payroll Status',
            ],
            fn (array $row): array => [
                $row['name'],
                $row['email'],
                $row['department'],
                $row['branch'],
                $row['marked_days'],
                $row['present_units'],
                $row['attendance_percent'],
                $row['avg_hours'],
                $row['approved_leave_days'],
                $row['pending_leave_requests'],
                $row['payroll_entries'],
                $row['paid_entries'],
                $row['net_salary'],
                $row['latest_payroll_month'],
                $row['latest_payroll_status'],
            ],
        ];
    }
}
