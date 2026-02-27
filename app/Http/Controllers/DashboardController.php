<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollStructure;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DashboardController extends BaseController
{
    public function admin(Request $request): View
    {
        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        $sharedData = $this->sharedDashboardData();
        $sharedData['moduleStats'] = $this->moduleStats($employeeIds);
        $sharedData['latestEmployees'] = $this->latestEmployees($employeeIds);

        return view('dashboard.admin', [
            ...$sharedData,
            'latestUsers' => $this->latestUsers(),
            'dashboardFilterOptions' => [
                'branches' => Branch::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn (Branch $branch): array => [
                        'id' => (int) $branch->id,
                        'name' => (string) $branch->name,
                    ])
                    ->values()
                    ->all(),
                'departments' => Department::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(static fn (Department $department): array => [
                        'id' => (int) $department->id,
                        'name' => (string) $department->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'dashboardFilterState' => [
                'branchId' => $filterContext['branchId'],
                'departmentId' => $filterContext['departmentId'],
            ],
        ]);
    }

    public function adminSummary(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminSummaryPayload($request));
    }

    public function adminAttendanceOverview(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminAttendanceOverviewPayload($request));
    }

    public function adminLeaveOverview(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminLeaveOverviewPayload($request));
    }

    public function adminWorkHoursAvg(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        $range = (string) $request->query('range', '30d');
        $fromParam = (string) $request->query('from', '');
        $toParam = (string) $request->query('to', '');

        $today = now()->startOfDay();
        $from = $today->copy()->subDays(29);
        $to = $today->copy();
        $label = 'Last 30 Days';

        if ($range === '7d') {
            $from = $today->copy()->subDays(6);
            $to = $today->copy();
            $label = 'Last 7 Days';
        } elseif ($range === 'month') {
            $from = now()->copy()->startOfMonth()->startOfDay();
            $to = now()->copy()->endOfMonth()->startOfDay();
            $label = 'This Month';
        } elseif ($range === 'custom') {
            try {
                $from = $fromParam !== '' ? \Carbon\Carbon::parse($fromParam)->startOfDay() : $from;
                $to = $toParam !== '' ? \Carbon\Carbon::parse($toParam)->startOfDay() : $to;
                if ($from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }
                $label = 'Custom Range';
            } catch (\Throwable) {
                // fall back silently
            }
        }

        $periodDays = max(1, $from->copy()->diffInDays($to->copy()) + 1);

        // Build day buckets for the range (inclusive)
        $dayBuckets = collect(range(0, $periodDays - 1))
            ->mapWithKeys(function (int $offset) use ($from): array {
                $day = $from->copy()->addDays($offset);
                $key = $day->toDateString();
                return [
                    $key => [
                        'date' => $day,
                        'sum_minutes' => 0,
                        'count_records' => 0,
                    ],
                ];
            });

        if ($employeeIds->count() > 0) {
            $rows = Attendance::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
                ->whereNotNull('work_minutes')
                ->selectRaw('DATE(attendance_date) as day')
                ->selectRaw('COALESCE(SUM(work_minutes), 0) as sum_minutes')
                ->selectRaw('COUNT(work_minutes) as records_count')
                ->groupBy('day')
                ->get();

            foreach ($rows as $row) {
                $key = (string) ($row->day ?? '');
                if ($key === '' || ! $dayBuckets->has($key)) continue;
                $bucket = (array) $dayBuckets->get($key);
                $bucket['sum_minutes'] = (int) ($row->sum_minutes ?? 0);
                $bucket['count_records'] = (int) ($row->records_count ?? 0);
                $dayBuckets->put($key, $bucket);
            }
        }

        $series = $dayBuckets->values()->map(function (array $item): array {
            $sum = (int) ($item['sum_minutes'] ?? 0);
            $count = (int) ($item['count_records'] ?? 0);
            $avgHours = $count > 0 ? round(($sum / $count) / 60, 2) : 0.0;
            return [
                'iso' => $item['date']->toDateString(),
                'label' => $item['date']->format('M d'),
                'avg_hours' => $avgHours,
            ];
        })->values();

        $totalSum = (int) $dayBuckets->sum('sum_minutes');
        $totalCount = (int) $dayBuckets->sum('count_records');
        $overallAvgHours = $totalCount > 0 ? round(($totalSum / $totalCount) / 60, 2) : 0.0;

        // Previous comparable period for trend
        $prevFrom = $from->copy()->subDays($periodDays);
        $prevTo = $from->copy()->subDay();
        $prevTotalSum = 0;
        $prevTotalCount = 0;
        if ($employeeIds->count() > 0) {
            $prevAgg = Attendance::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('attendance_date', [$prevFrom->toDateString(), $prevTo->toDateString()])
                ->whereNotNull('work_minutes')
                ->selectRaw('COALESCE(SUM(work_minutes), 0) as sum_minutes')
                ->selectRaw('COUNT(work_minutes) as records_count')
                ->first();
            $prevTotalSum = (int) ($prevAgg?->sum_minutes ?? 0);
            $prevTotalCount = (int) ($prevAgg?->records_count ?? 0);
        }
        $prevAvgHours = $prevTotalCount > 0 ? ($prevTotalSum / $prevTotalCount) / 60 : 0.0;
        $trendPct = $prevAvgHours > 0 ? round((($overallAvgHours - $prevAvgHours) / $prevAvgHours) * 100, 1) : null;
        $trendDirection = $trendPct === null ? 'flat' : ($trendPct > 0 ? 'up' : ($trendPct < 0 ? 'down' : 'flat'));

        $payload = [
            'generatedAt' => now()->toIso8601String(),
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $label,
                'range' => $range,
            ],
            'metric' => [
                'valueHours' => $overallAvgHours,
                'trendPct' => $trendPct,
                'trendDirection' => $trendDirection,
            ],
            'chart' => [
                'series' => $series,
                'benchmarkHours' => 8.0,
            ],
        ];

        return response()->json($payload);
    }

    public function adminWorkHoursMonthly(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        $months = collect(range(0, 11))
            ->map(fn (int $offset) => now()->copy()->startOfMonth()->subMonths(11 - $offset));
        $from = $months->first()->copy()->startOfMonth();
        $to = $months->last()->copy()->endOfMonth();

        $baselineMinutes = 8 * 60; // 8 hours per day

        $buckets = $months->mapWithKeys(function ($month) {
            $key = $month->format('Y-m');
            return [
                $key => [
                    'month' => $month,
                    'work_minutes' => 0,
                    'overtime_minutes' => 0,
                ],
            ];
        });

        if ($employeeIds->count() > 0) {
            $rows = Attendance::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
                ->whereNotNull('work_minutes')
                ->get(['attendance_date', 'work_minutes']);

            foreach ($rows as $row) {
                $date = optional($row->attendance_date)->copy() ?: now();
                $key = $date->format('Y-m');
                if (! $buckets->has($key)) continue;
                $wm = (int) ($row->work_minutes ?? 0);
                $bucket = (array) $buckets->get($key);
                $bucket['work_minutes'] += $wm;
                $bucket['overtime_minutes'] += max(0, $wm - $baselineMinutes);
                $buckets->put($key, $bucket);
            }
        }

        $formatHm = function (int $minutes): string {
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;
            return sprintf('%dh %02dm', $h, $m);
        };

        $series = $buckets->values()->map(function (array $item): array {
            return [
                'label' => $item['month']->format('M y'),
                'work_hours' => round(($item['work_minutes'] ?? 0) / 60, 1),
                'overtime_hours' => round(($item['overtime_minutes'] ?? 0) / 60, 1),
            ];
        })->values();

        $currentKey = now()->format('Y-m');
        $current = (array) ($buckets->get($currentKey) ?? ['work_minutes' => 0, 'overtime_minutes' => 0]);

        $payload = [
            'generatedAt' => now()->toIso8601String(),
            'period' => [
                'fromMonth' => $from->format('Y-m'),
                'toMonth' => $to->format('Y-m'),
            ],
            'summary' => [
                'workTimeLabel' => $formatHm((int) ($current['work_minutes'] ?? 0)),
                'overtimeLabel' => $formatHm((int) ($current['overtime_minutes'] ?? 0)),
            ],
            'chart' => [
                'series' => $series,
            ],
        ];

        return response()->json($payload);
    }

    public function hr(): View
    {
        return view('dashboard.hr', $this->sharedDashboardData());
    }

    public function employee(): View
    {
        return view('dashboard.employee', [
            'modules' => $this->hrModules(),
            ...$this->employeeDashboardData(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedDashboardData(): array
    {
        return [
            'modules' => $this->hrModules(),
            'employeeStats' => $this->employeeStats(),
            'moduleStats' => $this->moduleStats(),
            'latestEmployees' => $this->latestEmployees(),
            'recentActivities' => $this->recentActivities(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeDashboardData(): array
    {
        $viewer = auth()->user();
        if (! $viewer instanceof User) {
            return [
                'employeeSnapshot' => [
                    'attendanceScore' => 0.0,
                    'presentUnits' => 0.0,
                    'monthDays' => 0,
                    'approvedLeaveDays' => 0.0,
                    'pendingLeaves' => 0,
                    'remainingLeave' => 0.0,
                    'latestPayrollStatus' => 'not_generated',
                    'latestPayrollNet' => 0.0,
                    'thisYearNet' => 0.0,
                    'payrollPending' => 0,
                    'nextApprovedLeaveDate' => null,
                ],
            ];
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthDays = (int) $monthStart->daysInMonth;

        $attendanceRecords = Attendance::query()
            ->where('user_id', $viewer->id)
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['status']);

        $presentUnits = (float) $attendanceRecords->sum(function (Attendance $attendance): float {
            return match ($attendance->status) {
                Attendance::STATUS_PRESENT, Attendance::STATUS_REMOTE => 1.0,
                Attendance::STATUS_HALF_DAY => 0.5,
                default => 0.0,
            };
        });

        $attendanceScore = $monthDays > 0
            ? round(min(100, max(0, ($presentUnits / $monthDays) * 100)), 1)
            : 0.0;

        $approvedLeaveDays = (float) LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', now()->year)
            ->sum('total_days');

        $pendingLeaves = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        $annualAllowance = 24.0;
        $remainingLeave = max(0.0, $annualAllowance - $approvedLeaveDays);

        $latestPayroll = null;
        $thisYearNet = 0.0;
        $payrollPending = 0;
        if (Schema::hasTable('payrolls')) {
            $latestPayroll = Payroll::query()
                ->where('user_id', $viewer->id)
                ->orderByDesc('payroll_month')
                ->orderByDesc('id')
                ->first();

            $thisYearNet = (float) Payroll::query()
                ->where('user_id', $viewer->id)
                ->whereYear('payroll_month', now()->year)
                ->sum('net_salary');

            $payrollPending = Payroll::query()
                ->where('user_id', $viewer->id)
                ->whereIn('status', [Payroll::STATUS_DRAFT, Payroll::STATUS_PROCESSED])
                ->count();
        }

        $nextApprovedLeave = LeaveRequest::query()
            ->where('user_id', $viewer->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->first();

        return [
            'employeeSnapshot' => [
                'attendanceScore' => $attendanceScore,
                'presentUnits' => $presentUnits,
                'monthDays' => $monthDays,
                'approvedLeaveDays' => $approvedLeaveDays,
                'pendingLeaves' => $pendingLeaves,
                'remainingLeave' => $remainingLeave,
                'latestPayrollStatus' => (string) ($latestPayroll?->status ?? 'not_generated'),
                'latestPayrollNet' => (float) ($latestPayroll?->net_salary ?? 0),
                'thisYearNet' => $thisYearNet,
                'payrollPending' => $payrollPending,
                'nextApprovedLeaveDate' => $nextApprovedLeave?->start_date,
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function employeeStats(): array
    {
        $employeeUsers = User::query()->workforce();
        $employeeIds = (clone $employeeUsers)->pluck('id');

        $active = UserProfile::query()->whereIn('user_id', $employeeIds)->where('status', 'active')->count();
        $inactive = UserProfile::query()->whereIn('user_id', $employeeIds)->where('status', 'inactive')->count();
        $suspended = UserProfile::query()->whereIn('user_id', $employeeIds)->where('status', 'suspended')->count();

        return [
            'total' => $employeeIds->count(),
            'active' => $active,
            'inactive' => $inactive,
            'suspended' => $suspended,
            'newJoiners' => UserProfile::query()
                ->whereIn('user_id', $employeeIds)
                ->whereDate('joined_on', '>=', now()->subDays(30))
                ->count(),
            'needsAttention' => $inactive + $suspended,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function moduleStats(?Collection $employeeIds = null): array
    {
        $employeeIds = $employeeIds instanceof Collection
            ? $employeeIds
            : User::query()
                ->workforce()
                ->pluck('id');

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $attendanceTodayQuery = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $today);

        $presentToday = (clone $attendanceTodayQuery)
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_REMOTE,
            ])
            ->count();

        $leaveMonthQuery = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('start_date', [$monthStart, $monthEnd]);

        $payrollGenerated = 0;
        $payrollPaid = 0;
        $payrollNetMonth = 0.0;
        if (Schema::hasTable('payrolls')) {
            $payrollMonthQuery = Payroll::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('payroll_month', [$monthStart, $monthEnd]);

            $payrollGenerated = (clone $payrollMonthQuery)->count();
            $payrollPaid = (clone $payrollMonthQuery)
                ->where('status', Payroll::STATUS_PAID)
                ->count();
            $payrollNetMonth = (float) ((clone $payrollMonthQuery)->sum('net_salary'));
        }

        $payrollStructuresTotal = 0;
        if (Schema::hasTable('payroll_structures')) {
            $payrollStructuresTotal = PayrollStructure::query()
                ->whereIn('user_id', $employeeIds)
                ->count();
        }

        return [
            'usersTotal' => User::query()->count(),
            'adminsTotal' => User::query()->where('role', UserRole::ADMIN->value)->count(),
            'hrTotal' => User::query()->where('role', UserRole::HR->value)->count(),
            'employeesTotal' => $employeeIds->count(),
            'departmentsTotal' => Department::query()->count(),
            'branchesTotal' => Branch::query()->count(),
            'payrollStructuresTotal' => $payrollStructuresTotal,
            'attendanceMarkedToday' => (clone $attendanceTodayQuery)->count(),
            'attendancePresentToday' => $presentToday,
            'leavePending' => LeaveRequest::query()
                ->whereIn('user_id', $employeeIds)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->count(),
            'leaveApprovedMonth' => (clone $leaveMonthQuery)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->count(),
            'payrollGeneratedMonth' => $payrollGenerated,
            'payrollPaidMonth' => $payrollPaid,
            'payrollPendingMonth' => max(0, $payrollGenerated - $payrollPaid),
            'payrollNetMonth' => $payrollNetMonth,
        ];
    }

    /**
     * @return array{branch_id:int|null,department_id:int|null}
     */
    private function validatedAdminEmployeeFilters(Request $request): array
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        return [
            'branch_id' => isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'department_id' => isset($validated['department_id']) ? (int) $validated['department_id'] : null,
        ];
    }

    /**
     * @return array{
     *  branchId:int|null,
     *  departmentId:int|null,
     *  branchNameKey:string|null,
     *  departmentNameKey:string|null
     * }
     */
    private function resolveAdminEmployeeFilterContext(?int $branchId, ?int $departmentId): array
    {
        $branchName = null;
        if ($branchId !== null) {
            $resolvedBranchName = Branch::query()->whereKey($branchId)->value('name');
            if (is_string($resolvedBranchName) && trim($resolvedBranchName) !== '') {
                $branchName = trim($resolvedBranchName);
            }
        }

        $departmentName = null;
        if ($departmentId !== null) {
            $resolvedDepartmentName = Department::query()->whereKey($departmentId)->value('name');
            if (is_string($resolvedDepartmentName) && trim($resolvedDepartmentName) !== '') {
                $departmentName = trim($resolvedDepartmentName);
            }
        }

        return [
            'branchId' => $branchId,
            'departmentId' => $departmentId,
            'branchNameKey' => $branchName !== null ? mb_strtolower(trim($branchName)) : null,
            'departmentNameKey' => $departmentName !== null ? mb_strtolower(trim($departmentName)) : null,
        ];
    }

    /**
     * @param array{
     *  branchNameKey:string|null,
     *  departmentNameKey:string|null
     * } $filterContext
     *
     * @return Collection<int, int>
     */
    private function filteredEmployeeIds(array $filterContext): Collection
    {
        return User::query()
            ->workforce()
            ->when(($filterContext['branchNameKey'] ?? null) !== null, function (Builder $query) use ($filterContext): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($filterContext): void {
                    $profileQuery->whereRaw('LOWER(TRIM(branch)) = ?', [$filterContext['branchNameKey']]);
                });
            })
            ->when(($filterContext['departmentNameKey'] ?? null) !== null, function (Builder $query) use ($filterContext): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($filterContext): void {
                    $profileQuery->whereRaw('LOWER(TRIM(department)) = ?', [$filterContext['departmentNameKey']]);
                });
            })
            ->pluck('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSummaryPayload(Request $request): array
    {
        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        try {
            $contextDate = \Carbon\Carbon::parse((string) $request->query('date', ''));
        } catch (\Throwable) {
            $contextDate = now();
        }
        $today = $contextDate->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $activeEmployees = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', 'active')
            ->count();

        $presentToday = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $today)
            ->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_REMOTE,
            ])
            ->count();

        $presentPercentage = $activeEmployees > 0
            ? round(($presentToday / $activeEmployees) * 100, 1)
            : 0.0;

        $employeesOnLeave = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $pendingLeaveApprovals = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        $payrollCompleted = 0;
        $payrollPending = 0;
        if (Schema::hasTable('payrolls')) {
            $payrollMonthQuery = Payroll::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('payroll_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

            $payrollCompleted = (clone $payrollMonthQuery)
                ->where('status', Payroll::STATUS_PAID)
                ->count();

            $payrollPending = (clone $payrollMonthQuery)
                ->whereIn('status', [Payroll::STATUS_DRAFT, Payroll::STATUS_PROCESSED])
                ->count();
        }

        $pendingOtherApprovals = $payrollPending;

        $newJoinersThisMonth = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('joined_on', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        $exitsByStatus = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereIn('status', ['inactive', 'suspended'])
            ->whereNotNull('joined_on')
            ->whereBetween('updated_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
            ->count();

        return [
            'summary' => [
                'totalActiveEmployees' => $activeEmployees,
                'presentToday' => [
                    'count' => $presentToday,
                    'percentage' => $presentPercentage,
                ],
                'employeesOnLeave' => $employeesOnLeave,
                'pendingApprovals' => [
                    'total' => $pendingLeaveApprovals + $pendingOtherApprovals,
                    'leave' => $pendingLeaveApprovals,
                    'other' => $pendingOtherApprovals,
                ],
                'payrollStatus' => [
                    'completed' => $payrollCompleted,
                    'pending' => $payrollPending,
                    'state' => $payrollPending === 0 ? 'Completed' : 'Pending',
                ],
                'newJoinersThisMonth' => $newJoinersThisMonth,
                'exitsThisMonth' => $exitsByStatus,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminAttendanceOverviewPayload(Request $request): array
    {
        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );

        try {
            $contextDate = \Carbon\Carbon::parse((string) $request->query('date', ''));
        } catch (\Throwable) {
            $contextDate = now();
        }
        $today = $contextDate->toDateString();
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        $totalEmployees = $employeeIds->count();
        if ($totalEmployees === 0) {
            return [
                'generatedAt' => now()->toIso8601String(),
                'totals' => [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'onLeave' => 0,
                    'workFromHome' => 0,
                    'notMarked' => 0,
                    'totalEmployees' => 0,
                ],
                'departments' => [],
            ];
        }

        $todayAttendance = Attendance::query()
            ->whereIn('user_id', $employeeIds)
            ->whereDate('attendance_date', $today)
            ->get(['user_id', 'status', 'check_in_at']);

        $attendanceByUserId = $todayAttendance->keyBy('user_id');
        $markedToday = $attendanceByUserId->count();

        $presentStatuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_HALF_DAY,
        ];

        $presentForDepartmentStatuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_HALF_DAY,
            Attendance::STATUS_REMOTE,
        ];

        $present = $todayAttendance
            ->whereIn('status', $presentStatuses)
            ->count();

        $absent = $todayAttendance
            ->where('status', Attendance::STATUS_ABSENT)
            ->count();

        $workFromHome = $todayAttendance
            ->where('status', Attendance::STATUS_REMOTE)
            ->count();

        $late = $todayAttendance
            ->filter(function (Attendance $attendance): bool {
                if (! in_array($attendance->status, [
                    Attendance::STATUS_PRESENT,
                    Attendance::STATUS_HALF_DAY,
                    Attendance::STATUS_REMOTE,
                ], true)) {
                    return false;
                }

                return $attendance->check_in_at?->format('H:i:s') > '09:30:00';
            })
            ->count();

        $onLeaveMarked = $todayAttendance
            ->where('status', Attendance::STATUS_ON_LEAVE)
            ->count();

        $approvedOnLeaveUserIds = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id')
            ->unique();

        $onLeaveWithoutAttendance = $approvedOnLeaveUserIds
            ->filter(fn ($userId): bool => ! $attendanceByUserId->has((int) $userId))
            ->count();

        $onLeave = $onLeaveMarked + $onLeaveWithoutAttendance;
        $notMarked = max(0, ($totalEmployees - $markedToday) - $onLeaveWithoutAttendance);

        $employees = User::query()
            ->whereIn('id', $employeeIds)
            ->with('profile:user_id,department')
            ->get(['id']);

        $departments = $employees
            ->groupBy(function (User $user): string {
                $department = trim((string) ($user->profile?->department ?? ''));

                return $department !== '' ? $department : 'Unassigned';
            })
            ->map(function ($departmentUsers, string $departmentName) use ($attendanceByUserId, $presentForDepartmentStatuses): array {
                $departmentTotal = $departmentUsers->count();
                $departmentPresent = $departmentUsers
                    ->filter(function (User $user) use ($attendanceByUserId, $presentForDepartmentStatuses): bool {
                        $attendance = $attendanceByUserId->get($user->id);
                        if (! $attendance instanceof Attendance) {
                            return false;
                        }

                        return in_array((string) $attendance->status, $presentForDepartmentStatuses, true);
                    })
                    ->count();

                $percentage = $departmentTotal > 0
                    ? round(($departmentPresent / $departmentTotal) * 100, 1)
                    : 0.0;

                return [
                    'name' => $departmentName,
                    'present' => $departmentPresent,
                    'total' => $departmentTotal,
                    'percentage' => $percentage,
                ];
            })
            ->sortByDesc('percentage')
            ->values()
            ->all();

        return [
            'generatedAt' => now()->toIso8601String(),
            'totals' => [
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'onLeave' => $onLeave,
                'workFromHome' => $workFromHome,
                'notMarked' => $notMarked,
                'totalEmployees' => $totalEmployees,
            ],
            'departments' => $departments,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminLeaveOverviewPayload(Request $request): array
    {
        $validatedFilters = $this->validatedAdminEmployeeFilters($request);
        $filterContext = $this->resolveAdminEmployeeFilterContext(
            $validatedFilters['branch_id'],
            $validatedFilters['department_id'],
        );
        $employeeIds = $this->filteredEmployeeIds($filterContext);

        try {
            $contextDate = \Carbon\Carbon::parse((string) $request->query('date', ''));
        } catch (\Throwable $e) {
            $contextDate = now();
        }
        $today = $contextDate->toDateString();
        $monthStart = $contextDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $contextDate->copy()->endOfMonth()->toDateString();

        $pendingApprovals = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        $approvedLeaves = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->count();

        $leaveTypeBreakdownRow = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN leave_type = 'sick' THEN 1 ELSE 0 END), 0) AS sick")
            ->selectRaw("COALESCE(SUM(CASE WHEN leave_type = 'casual' THEN 1 ELSE 0 END), 0) AS casual")
            ->selectRaw("COALESCE(SUM(CASE WHEN leave_type IN ('earned', 'paid') THEN 1 ELSE 0 END), 0) AS paid")
            ->first();

        $sickLeaves = (int) ($leaveTypeBreakdownRow?->sick ?? 0);
        $casualLeaves = (int) ($leaveTypeBreakdownRow?->casual ?? 0);
        $paidLeaves = (int) ($leaveTypeBreakdownRow?->paid ?? 0);

        $employeesOnLeaveToday = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->distinct()
            ->count('user_id');

        $filterRouteParams = [];
        if ($filterContext['branchId'] !== null) {
            $filterRouteParams['branch_id'] = $filterContext['branchId'];
        }
        if ($filterContext['departmentId'] !== null) {
            $filterRouteParams['department_id'] = $filterContext['departmentId'];
        }

        return [
            'generatedAt' => now()->toIso8601String(),
            'period' => [
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
                'today' => $today,
            ],
            'metrics' => [
                'pendingApprovals' => $pendingApprovals,
                'approvedLeaves' => $approvedLeaves,
                'employeesOnLeaveToday' => $employeesOnLeaveToday,
            ],
            'leaveTypeBreakdown' => [
                'sick' => $sickLeaves,
                'casual' => $casualLeaves,
                'paid' => $paidLeaves,
            ],
            'actions' => [
                'pendingApprovalsUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_PENDING,
                ], $filterRouteParams)),
                'approvedLeavesUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ], $filterRouteParams)),
                'sickLeavesUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_SICK,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ], $filterRouteParams)),
                'casualLeavesUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_CASUAL,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ], $filterRouteParams)),
                'paidLeavesUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_EARNED,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ], $filterRouteParams)),
                'employeesOnLeaveTodayUrl' => route('modules.leave.index', array_merge([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'on_date' => $today,
                ], $filterRouteParams)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    // Legacy adminCharts() removed as part of dashboard cleanup.

    /**
     * @return Collection<int, User>
     */
    private function latestEmployees(?Collection $employeeIds = null): Collection
    {
        return User::query()
            ->workforce()
            ->when($employeeIds instanceof Collection, function (Builder $query) use ($employeeIds): void {
                $query->whereIn('id', $employeeIds);
            })
            ->with('profile')
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function latestUsers(): Collection
    {
        return User::query()->latest()->limit(5)->get();
    }

    /**
     * @return Collection<int, array{title:string,meta:string,tone:string,occurred_at:\Illuminate\Support\Carbon}>
     */
    private function recentActivities(): Collection
    {
        if (Schema::hasTable('activities')) {
            $activityFeed = Activity::query()
                ->with('actor')
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->map(function (Activity $activity): array {
                    $occurredAt = $activity->occurred_at ?? $activity->created_at ?? now();
                    $actorName = $activity->actor?->name ?? 'System';
                    $baseMeta = $activity->meta ?: $actorName;

                    return [
                        'title' => (string) $activity->title,
                        'meta' => "{$baseMeta} • {$occurredAt->diffForHumans()}",
                        'tone' => (string) ($activity->tone ?: '#7c3aed'),
                        'occurred_at' => $occurredAt,
                    ];
                })
                ->take(8)
                ->values();

            if ($activityFeed->isNotEmpty()) {
                return $activityFeed;
            }
        }

        return $this->legacyRecentActivities();
    }

    /**
     * @return Collection<int, array{title:string,meta:string,tone:string,occurred_at:\Illuminate\Support\Carbon}>
     */
    private function legacyRecentActivities(): Collection
    {
        $userEvents = User::query()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (User $user): array {
                $roleLabel = $user->role instanceof UserRole ? $user->role->label() : ucfirst((string) $user->role);
                $occurredAt = $user->created_at ?? now();

                return [
                    'title' => "{$roleLabel} account created",
                    'meta' => "{$user->full_name} • {$occurredAt->diffForHumans()}",
                    'tone' => '#7c3aed',
                    'occurred_at' => $occurredAt,
                ];
            });

        $profileUpdateEvents = UserProfile::query()
            ->with('user')
            ->whereColumn('updated_at', '>', 'created_at')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function (UserProfile $profile): array {
                $name = $profile->user?->full_name ?? 'Unknown user';
                $occurredAt = $profile->updated_at ?? now();

                return [
                    'title' => 'Employee profile updated',
                    'meta' => "{$name} • {$occurredAt->diffForHumans()}",
                    'tone' => '#ec4899',
                    'occurred_at' => $occurredAt,
                ];
            });

        $joinerEvents = UserProfile::query()
            ->with('user')
            ->whereNotNull('joined_on')
            ->latest('joined_on')
            ->limit(10)
            ->get()
            ->map(function (UserProfile $profile): array {
                $name = $profile->user?->full_name ?? 'Unknown user';
                $joinedOn = $profile->joined_on?->startOfDay() ?? now();

                return [
                    'title' => 'Employee joined',
                    'meta' => "{$name} • {$joinedOn->format('M d, Y')}",
                    'tone' => '#3b82f6',
                    'occurred_at' => $joinedOn,
                ];
            });

        $attendanceEvents = Attendance::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (Attendance $attendance): array {
                $name = $attendance->user?->full_name ?? 'Unknown employee';
                $occurredAt = $attendance->updated_at ?? now();
                $status = str($attendance->status)->replace('_', ' ')->title();

                return [
                    'title' => "Attendance marked: {$status}",
                    'meta' => "{$name} • {$occurredAt->diffForHumans()}",
                    'tone' => '#0ea5e9',
                    'occurred_at' => $occurredAt,
                ];
            });

        $leaveEvents = LeaveRequest::query()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (LeaveRequest $leaveRequest): array {
                $name = $leaveRequest->user?->full_name ?? 'Unknown employee';
                $occurredAt = $leaveRequest->updated_at ?? now();
                $status = str($leaveRequest->status)->replace('_', ' ')->title();

                return [
                    'title' => "Leave {$status}",
                    'meta' => "{$name} • {$occurredAt->diffForHumans()}",
                    'tone' => '#f59e0b',
                    'occurred_at' => $occurredAt,
                ];
            });

        $payrollEvents = collect();
        if (Schema::hasTable('payrolls')) {
            $payrollEvents = Payroll::query()
                ->with('user')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function (Payroll $payroll): array {
                    $name = $payroll->user?->full_name ?? 'Unknown employee';
                    $occurredAt = $payroll->updated_at ?? now();
                    $status = str($payroll->status)->replace('_', ' ')->title();

                    return [
                        'title' => "Payroll {$status}",
                        'meta' => "{$name} • {$payroll->payroll_month?->format('M Y')}",
                        'tone' => '#10b981',
                        'occurred_at' => $occurredAt,
                    ];
                });
        }

        return $userEvents
            ->merge($profileUpdateEvents)
            ->merge($joinerEvents)
            ->merge($attendanceEvents)
            ->merge($leaveEvents)
            ->merge($payrollEvents)
            ->sortByDesc('occurred_at')
            ->take(8)
            ->values();
    }
}
