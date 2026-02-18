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
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->where('role', UserRole::EMPLOYEE->value);
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

        $employeeQuery = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('profile')
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $innerQuery) use ($filters): void {
                    $innerQuery
                        ->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('email', 'like', "%{$filters['q']}%");
                });
            });

        if ($isManagement) {
            if ($filters['employee_id'] > 0) {
                $employeeQuery->whereKey($filters['employee_id']);
            }

            if ($filters['department'] !== '') {
                $employeeQuery->whereHas('profile', function (Builder $query) use ($filters): void {
                    $query->where('department', $filters['department']);
                });
            }

            if ($filters['branch'] !== '') {
                $employeeQuery->whereHas('profile', function (Builder $query) use ($filters): void {
                    $query->where('branch', $filters['branch']);
                });
            }
        } else {
            $employeeQuery->whereKey($viewer->id);
        }

        $employeeIds = (clone $employeeQuery)->pluck('id');
        $employeeCount = $employeeIds->count();
        $periodDays = max(
            1,
            (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1
        );

        $attendanceBase = Attendance::query()
            ->whereIn('user_id', $employeeIds)
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
            ->whereIn('user_id', $employeeIds)
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereDate('start_date', '<=', $to->toDateString())
                    ->whereDate('end_date', '>=', $from->toDateString());
            });

        $leaveTotals = (clone $leaveBase)
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END), 0) as approved_days")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests")
            ->first();

        $leaveStatusBreakdown = (clone $leaveBase)
            ->selectRaw('status, COUNT(*) as request_count')
            ->groupBy('status')
            ->orderByDesc('request_count')
            ->get();

        $leaveTypeBreakdown = (clone $leaveBase)
            ->selectRaw('leave_type, COUNT(*) as request_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END), 0) as approved_days")
            ->groupBy('leave_type')
            ->orderByDesc('request_count')
            ->get();

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
                ->whereIn('user_id', $employeeIds)
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
            'leaveRequests' => (int) ($leaveTotals?->total_requests ?? 0),
            'leaveApprovedDays' => round((float) ($leaveTotals?->approved_days ?? 0), 1),
            'leavePendingRequests' => (int) ($leaveTotals?->pending_requests ?? 0),
            'payrollEntries' => (int) ($payrollTotals?->generated_entries ?? 0),
            'payrollPaidEntries' => (int) ($payrollTotals?->paid_entries ?? 0),
            'payrollNet' => round((float) ($payrollTotals?->net_salary ?? 0), 2),
            'payrollDeductions' => round((float) ($payrollTotals?->total_deductions ?? 0), 2),
        ];

        if ($filters['export'] === 'csv') {
            $allEmployees = (clone $employeeQuery)
                ->orderBy('name')
                ->get();
            $rows = $this->buildEmployeeSummaryRows($allEmployees, $from, $to, $payrollEnabled);

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

            return $this->exportCsv($rows, $from, $to, $reportType, $reportTypeLabel);
        }

        $employees = (clone $employeeQuery)
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
                    $query->where('role', UserRole::EMPLOYEE->value);
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
                    $query->where('role', UserRole::EMPLOYEE->value);
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
            'leaveStatusBreakdown' => $leaveStatusBreakdown,
            'leaveTypeBreakdown' => $leaveTypeBreakdown,
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

            $stats['total'] = (clone $activityQuery)->count();
            $stats['uniqueActors'] = (clone $activityQuery)
                ->whereNotNull('actor_user_id')
                ->distinct('actor_user_id')
                ->count('actor_user_id');
            $stats['systemEvents'] = (clone $activityQuery)
                ->whereNull('actor_user_id')
                ->count();

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

    public function activityShow(Request $request, Activity $activity): View
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

        if (! $isManagement && (int) $activity->actor_user_id !== (int) $viewer->id) {
            abort(403);
        }

        $activity->loadMissing(['actor', 'subject']);

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
            'activity' => $activity,
            'backUrl' => $backUrl,
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
            ->where('role', UserRole::EMPLOYEE->value)
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

        $leaveByUser = LeaveRequest::query()
            ->whereIn('user_id', $ids)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->select('user_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN total_days ELSE 0 END), 0) as approved_days")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

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

    /**
     * @param Collection<int, array<string, int|float|string>> $rows
     */
    private function exportCsv(
        Collection $rows,
        Carbon $from,
        Carbon $to,
        string $reportType,
        string $reportTypeLabel
    ): StreamedResponse {
        [$headers, $resolver] = $this->csvColumnsForReportType($reportType);

        $filename = sprintf(
            'hr-%s-report-%s-to-%s.csv',
            str($reportType)->replace('_', '-'),
            $from->format('Ymd'),
            $to->format('Ymd')
        );

        return response()->streamDownload(function () use ($rows, $from, $to, $headers, $resolver, $reportTypeLabel): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Report Type', $reportTypeLabel]);
            fputcsv($handle, ['Report Period', $from->toDateString().' to '.$to->toDateString()]);
            fputcsv($handle, []);
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $resolver($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
