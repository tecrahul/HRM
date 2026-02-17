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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends BaseController
{
    public function admin(): View
    {
        return view('dashboard.admin', [
            ...$this->sharedDashboardData(),
            'latestUsers' => $this->latestUsers(),
            'adminCharts' => $this->adminCharts(),
        ]);
    }

    public function adminSummary(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminSummaryPayload());
    }

    public function adminAttendanceOverview(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminAttendanceOverviewPayload());
    }

    public function adminLeaveOverview(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        return response()->json($this->adminLeaveOverviewPayload());
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
        $employeeUsers = User::query()->where('role', UserRole::EMPLOYEE->value);
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
    private function moduleStats(): array
    {
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
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
     * @return array<string, mixed>
     */
    private function adminSummaryPayload(): array
    {
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');

        $today = now()->toDateString();
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
    private function adminAttendanceOverviewPayload(): array
    {
        $today = now()->toDateString();
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');

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
            ->where('role', UserRole::EMPLOYEE->value)
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
    private function adminLeaveOverviewPayload(): array
    {
        $employeeRole = UserRole::EMPLOYEE->value;
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $pendingApprovalsRow = DB::selectOne(
            <<<'SQL'
            SELECT COUNT(*) AS total
            FROM leave_requests lr
            INNER JOIN users u ON u.id = lr.user_id
            WHERE u.role = ?
              AND lr.status = 'pending'
            SQL,
            [$employeeRole]
        );

        $approvedLeavesRow = DB::selectOne(
            <<<'SQL'
            SELECT COUNT(*) AS total
            FROM leave_requests lr
            INNER JOIN users u ON u.id = lr.user_id
            WHERE u.role = ?
              AND lr.status = 'approved'
              AND lr.start_date BETWEEN ? AND ?
            SQL,
            [$employeeRole, $monthStart, $monthEnd]
        );

        $leaveTypeBreakdownRow = DB::selectOne(
            <<<'SQL'
            SELECT
                COALESCE(SUM(CASE WHEN lr.leave_type = 'sick' THEN 1 ELSE 0 END), 0) AS sick,
                COALESCE(SUM(CASE WHEN lr.leave_type = 'casual' THEN 1 ELSE 0 END), 0) AS casual,
                COALESCE(SUM(CASE WHEN lr.leave_type IN ('earned', 'paid') THEN 1 ELSE 0 END), 0) AS paid
            FROM leave_requests lr
            INNER JOIN users u ON u.id = lr.user_id
            WHERE u.role = ?
              AND lr.status = 'approved'
              AND lr.start_date BETWEEN ? AND ?
            SQL,
            [$employeeRole, $monthStart, $monthEnd]
        );

        $employeesOnLeaveTodayRow = DB::selectOne(
            <<<'SQL'
            SELECT COUNT(DISTINCT lr.user_id) AS total
            FROM leave_requests lr
            INNER JOIN users u ON u.id = lr.user_id
            WHERE u.role = ?
              AND lr.status = 'approved'
              AND lr.start_date <= ?
              AND lr.end_date >= ?
            SQL,
            [$employeeRole, $today, $today]
        );

        $pendingApprovals = (int) ($pendingApprovalsRow->total ?? 0);
        $approvedLeaves = (int) ($approvedLeavesRow->total ?? 0);
        $sickLeaves = (int) ($leaveTypeBreakdownRow->sick ?? 0);
        $casualLeaves = (int) ($leaveTypeBreakdownRow->casual ?? 0);
        $paidLeaves = (int) ($leaveTypeBreakdownRow->paid ?? 0);
        $employeesOnLeaveToday = (int) ($employeesOnLeaveTodayRow->total ?? 0);

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
                'pendingApprovalsUrl' => route('modules.leave.index', ['status' => LeaveRequest::STATUS_PENDING]),
                'approvedLeavesUrl' => route('modules.leave.index', [
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ]),
                'sickLeavesUrl' => route('modules.leave.index', [
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_SICK,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ]),
                'casualLeavesUrl' => route('modules.leave.index', [
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_CASUAL,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ]),
                'paidLeavesUrl' => route('modules.leave.index', [
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'leave_type' => LeaveRequest::TYPE_EARNED,
                    'date_from' => $monthStart,
                    'date_to' => $monthEnd,
                ]),
                'employeesOnLeaveTodayUrl' => route('modules.leave.index', [
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'on_date' => $today,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminCharts(): array
    {
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');
        $employeeCount = $employeeIds->count();

        $periodEnd = now()->startOfDay();
        $periodStart = $periodEnd->copy()->subDays(13);

        $dayBuckets = collect(range(0, 13))
            ->mapWithKeys(function (int $offset) use ($periodStart): array {
                $day = $periodStart->copy()->addDays($offset);
                $key = $day->toDateString();

                return [
                    $key => [
                        'date' => $day,
                        'attendance_marked' => 0,
                        'present_units' => 0.0,
                        'leave_created' => 0,
                        'leave_approved' => 0,
                    ],
                ];
            });

        if ($employeeCount > 0) {
            $attendanceRows = Attendance::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->selectRaw('DATE(attendance_date) as day')
                ->selectRaw('COUNT(*) as marked_count')
                ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('present', 'remote') THEN 1 WHEN status = 'half_day' THEN 0.5 ELSE 0 END), 0) as present_units")
                ->groupBy('day')
                ->get();

            foreach ($attendanceRows as $row) {
                $key = (string) ($row->day ?? '');
                if ($key === '' || ! $dayBuckets->has($key)) {
                    continue;
                }

                $bucket = (array) $dayBuckets->get($key);
                $bucket['attendance_marked'] = (int) ($row->marked_count ?? 0);
                $bucket['present_units'] = round((float) ($row->present_units ?? 0), 1);
                $dayBuckets->put($key, $bucket);
            }

            $leaveRows = LeaveRequest::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('created_at', [$periodStart->startOfDay(), $periodEnd->copy()->endOfDay()])
                ->selectRaw('DATE(created_at) as day')
                ->selectRaw('COUNT(*) as request_count')
                ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count")
                ->groupBy('day')
                ->get();

            foreach ($leaveRows as $row) {
                $key = (string) ($row->day ?? '');
                if ($key === '' || ! $dayBuckets->has($key)) {
                    continue;
                }

                $bucket = (array) $dayBuckets->get($key);
                $bucket['leave_created'] = (int) ($row->request_count ?? 0);
                $bucket['leave_approved'] = (int) ($row->approved_count ?? 0);
                $dayBuckets->put($key, $bucket);
            }
        }

        $days = $dayBuckets->values()
            ->map(function (array $item) use ($employeeCount): array {
                $date = $item['date'];
                $presentUnits = (float) ($item['present_units'] ?? 0);
                $coverage = $employeeCount > 0
                    ? round(min(100, max(0, ($presentUnits / $employeeCount) * 100)), 1)
                    : 0.0;

                return [
                    'iso' => $date->toDateString(),
                    'label' => $date->format('M d'),
                    'short_label' => $date->format('d M'),
                    'attendance_marked' => (int) ($item['attendance_marked'] ?? 0),
                    'present_units' => $presentUnits,
                    'attendance_coverage' => $coverage,
                    'leave_created' => (int) ($item['leave_created'] ?? 0),
                    'leave_approved' => (int) ($item['leave_approved'] ?? 0),
                ];
            })
            ->values();

        $avgCoverage = round((float) ($days->avg('attendance_coverage') ?? 0), 1);
        $latestCoverage = (float) ($days->last()['attendance_coverage'] ?? 0);
        $bestCoverage = (float) ($days->max('attendance_coverage') ?? 0);
        $leaveTotal = (int) $days->sum('leave_created');
        $leaveApproved = (int) $days->sum('leave_approved');
        $leavePeak = (int) max(1, (int) ($days->max('leave_created') ?? 1));
        $leaveApprovalRate = $leaveTotal > 0
            ? round(($leaveApproved / $leaveTotal) * 100, 1)
            : 0.0;

        return [
            'periodLabel' => "{$periodStart->format('M d')} - {$periodEnd->format('M d')}",
            'employeeCount' => $employeeCount,
            'days' => $days,
            'attendance' => [
                'averageCoverage' => $avgCoverage,
                'latestCoverage' => $latestCoverage,
                'bestCoverage' => $bestCoverage,
                'latestMarked' => (int) ($days->last()['attendance_marked'] ?? 0),
            ],
            'leave' => [
                'totalRequests' => $leaveTotal,
                'approvedRequests' => $leaveApproved,
                'approvalRate' => $leaveApprovalRate,
                'peakRequests' => $leavePeak,
            ],
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function latestEmployees(): Collection
    {
        return User::query()
            ->where('role', UserRole::EMPLOYEE->value)
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
                    'meta' => "{$user->name} • {$occurredAt->diffForHumans()}",
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
                $name = $profile->user?->name ?? 'Unknown user';
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
                $name = $profile->user?->name ?? 'Unknown user';
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
                $name = $attendance->user?->name ?? 'Unknown employee';
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
                $name = $leaveRequest->user?->name ?? 'Unknown employee';
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
                    $name = $payroll->user?->name ?? 'Unknown employee';
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
