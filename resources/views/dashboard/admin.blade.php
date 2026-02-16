@extends('layouts.dashboard-modern')

@section('title', 'Admin Dashboard')
@section('page_heading', 'Admin Command Center')

@section('content')
    <section class="ui-hero">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div>
                <p class="ui-kpi-label">Platform Overview</p>
                <h2 class="mt-2 text-2xl md:text-3xl font-extrabold">All Core Modules Are Live</h2>
                <p class="ui-section-subtitle">Monitor users, employees, attendance, leave, payroll, departments, and branches from one dashboard.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto">
                <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-primary">Manage Users</a>
                <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-ghost">Employee Directory</a>
                <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost">Run Payroll</a>
            </div>
        </div>
    </section>

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Total Users</p>
                    <p class="ui-kpi-value">{{ $moduleStats['usersTotal'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
            </div>
            <p class="ui-kpi-meta">Admin {{ $moduleStats['adminsTotal'] }} • HR {{ $moduleStats['hrTotal'] }} • Employees {{ $moduleStats['employeesTotal'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Attendance Today</p>
                    <p class="ui-kpi-value">{{ $moduleStats['attendanceMarkedToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Present/remote/half-day: {{ $moduleStats['attendancePresentToday'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Pending Leaves</p>
                    <p class="ui-kpi-value">{{ $moduleStats['leavePending'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Approved this month: {{ $moduleStats['leaveApprovedMonth'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Payroll Month</p>
                    <p class="ui-kpi-value">{{ $moduleStats['payrollGeneratedMonth'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Paid {{ $moduleStats['payrollPaidMonth'] }} • Pending {{ $moduleStats['payrollPendingMonth'] }}</p>
        </article>
    </section>

    @php
        $usersTotal = (int) $moduleStats['usersTotal'];
        $adminPct = $usersTotal > 0 ? round(((int) $moduleStats['adminsTotal'] / $usersTotal) * 100, 1) : 0.0;
        $hrPct = $usersTotal > 0 ? round(((int) $moduleStats['hrTotal'] / $usersTotal) * 100, 1) : 0.0;
        $employeePct = $usersTotal > 0 ? round(((int) $moduleStats['employeesTotal'] / $usersTotal) * 100, 1) : 0.0;
        $adminArcEnd = $adminPct;
        $hrArcEnd = $adminPct + $hrPct;
        $roleDonutStyle = $usersTotal > 0
            ? "conic-gradient(#2563eb 0% {$adminArcEnd}%, #7c3aed {$adminArcEnd}% {$hrArcEnd}%, #16a34a {$hrArcEnd}% 100%)"
            : 'conic-gradient(rgb(148 163 184 / 0.25) 0 100%)';

        $employeesTotal = (int) $moduleStats['employeesTotal'];
        $attendanceCoveragePct = $employeesTotal > 0
            ? round(min(100, ((int) $moduleStats['attendanceMarkedToday'] / $employeesTotal) * 100), 1)
            : 0.0;
        $leaveHandledTotal = (int) $moduleStats['leavePending'] + (int) $moduleStats['leaveApprovedMonth'];
        $leaveClearPct = $leaveHandledTotal > 0
            ? round(min(100, ((int) $moduleStats['leaveApprovedMonth'] / $leaveHandledTotal) * 100), 1)
            : 0.0;
        $payrollPaidPct = (int) $moduleStats['payrollGeneratedMonth'] > 0
            ? round(min(100, ((int) $moduleStats['payrollPaidMonth'] / (int) $moduleStats['payrollGeneratedMonth']) * 100), 1)
            : 0.0;
    @endphp

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">User Role Distribution</h3>
                    <p class="ui-section-subtitle">Breakdown of platform users by role.</p>
                </div>
            </div>

            <div class="mt-4 flex flex-col md:flex-row md:items-center gap-5">
                <div class="h-36 w-36 rounded-full p-3 shrink-0" style="background: {{ $roleDonutStyle }};">
                    <div class="h-full w-full rounded-full flex flex-col items-center justify-center text-center" style="background: var(--hr-surface-strong);">
                        <p class="text-[11px] uppercase tracking-[0.12em] font-bold" style="color: var(--hr-text-muted);">Users</p>
                        <p class="text-2xl font-extrabold">{{ $moduleStats['usersTotal'] }}</p>
                    </div>
                </div>

                <div class="w-full space-y-3 text-sm">
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="font-semibold">Admins</p>
                            <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ $moduleStats['adminsTotal'] }} • {{ number_format($adminPct, 1) }}%</p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                            <div class="h-full rounded-full" style="width: {{ $adminPct }}%; background: #2563eb;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="font-semibold">HR</p>
                            <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ $moduleStats['hrTotal'] }} • {{ number_format($hrPct, 1) }}%</p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                            <div class="h-full rounded-full" style="width: {{ $hrPct }}%; background: #7c3aed;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="font-semibold">Employees</p>
                            <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ $moduleStats['employeesTotal'] }} • {{ number_format($employeePct, 1) }}%</p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                            <div class="h-full rounded-full" style="width: {{ $employeePct }}%; background: #16a34a;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Operations Completion</h3>
                    <p class="ui-section-subtitle">Daily and monthly completion indicators.</p>
                </div>
            </div>

            <div class="mt-4 space-y-4 text-sm">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Attendance capture today</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ number_format($attendanceCoveragePct, 1) }}%</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $attendanceCoveragePct }}%; background: #0284c7;"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Leave queue cleared</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ number_format($leaveClearPct, 1) }}%</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $leaveClearPct }}%; background: #d97706;"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Payroll paid this month</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ number_format($payrollPaidPct, 1) }}%</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $payrollPaidPct }}%; background: #7c3aed;"></div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            @php
                $trendDays = collect($adminCharts['days'] ?? []);
                $trendCount = max(1, $trendDays->count());
                $attendancePoints = $trendDays->values()->map(function (array $day, int $index) use ($trendCount): string {
                    $x = $trendCount > 1 ? ($index / ($trendCount - 1)) * 100 : 0;
                    $coverage = max(0, min(100, (float) ($day['attendance_coverage'] ?? 0)));
                    $y = 100 - $coverage;

                    return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
                })->implode(' ');
                $attendanceAreaPath = $attendancePoints === ''
                    ? 'M 0 100 L 100 100 Z'
                    : "M 0 100 L {$attendancePoints} L 100 100 Z";
                $leaveScaleMax = max(1, (int) ($adminCharts['leave']['peakRequests'] ?? 1));
                $leaveRequestedPoints = $trendDays->values()->map(function (array $day, int $index) use ($trendCount, $leaveScaleMax): string {
                    $x = $trendCount > 1 ? ($index / ($trendCount - 1)) * 100 : 0;
                    $y = 100 - (min($leaveScaleMax, max(0, (int) ($day['leave_created'] ?? 0))) / $leaveScaleMax) * 100;

                    return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
                })->implode(' ');
                $leaveApprovedPoints = $trendDays->values()->map(function (array $day, int $index) use ($trendCount, $leaveScaleMax): string {
                    $x = $trendCount > 1 ? ($index / ($trendCount - 1)) * 100 : 0;
                    $y = 100 - (min($leaveScaleMax, max(0, (int) ($day['leave_approved'] ?? 0))) / $leaveScaleMax) * 100;

                    return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
                })->implode(' ');
            @endphp

            <div class="space-y-5">
                <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="ui-section-head">
                        <div>
                            <h3 class="ui-section-title">Attendance Trend</h3>
                            <p class="ui-section-subtitle">Default view for last 14 days ({{ $adminCharts['periodLabel'] }}).</p>
                        </div>
                        <a href="{{ route('modules.attendance.index') }}" class="ui-btn ui-btn-ghost">Open Attendance</a>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Avg Coverage</p>
                            <p class="mt-1 text-lg font-extrabold">{{ number_format((float) ($adminCharts['attendance']['averageCoverage'] ?? 0), 1) }}%</p>
                        </div>
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Today Coverage</p>
                            <p class="mt-1 text-lg font-extrabold">{{ number_format((float) ($adminCharts['attendance']['latestCoverage'] ?? 0), 1) }}%</p>
                        </div>
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Best Day</p>
                            <p class="mt-1 text-lg font-extrabold">{{ number_format((float) ($adminCharts['attendance']['bestCoverage'] ?? 0), 1) }}%</p>
                        </div>
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Employees</p>
                            <p class="mt-1 text-lg font-extrabold">{{ (int) ($adminCharts['employeeCount'] ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border p-3" style="border-color: var(--hr-line);">
                        <svg viewBox="0 0 100 100" class="w-full h-48" role="img" aria-label="Attendance coverage chart for last 14 days">
                            <defs>
                                <linearGradient id="attendanceFill" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#0284c7" stop-opacity="0.35"></stop>
                                    <stop offset="100%" stop-color="#0284c7" stop-opacity="0.02"></stop>
                                </linearGradient>
                            </defs>
                            <line x1="0" y1="75" x2="100" y2="75" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            <line x1="0" y1="50" x2="100" y2="50" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            <line x1="0" y1="25" x2="100" y2="25" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            <path d="{{ $attendanceAreaPath }}" fill="url(#attendanceFill)"></path>
                            @if ($attendancePoints !== '')
                                <polyline points="{{ $attendancePoints }}" fill="none" stroke="#0284c7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            @endif
                            @foreach($trendDays as $index => $day)
                                @php
                                    $x = $trendCount > 1 ? ($index / ($trendCount - 1)) * 100 : 0;
                                    $coverage = max(0, min(100, (float) ($day['attendance_coverage'] ?? 0)));
                                    $y = 100 - $coverage;
                                @endphp
                                <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($y, 2, '.', '') }}" r="1.1" fill="#0284c7"></circle>
                            @endforeach
                        </svg>
                        <div class="mt-2 grid grid-cols-7 md:grid-cols-14 gap-1 text-[10px] font-semibold" style="color: var(--hr-text-muted);">
                            @foreach($trendDays as $day)
                                <span class="text-center">{{ $day['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="ui-section-head">
                        <div>
                            <h3 class="ui-section-title">Leave Request Trend</h3>
                            <p class="ui-section-subtitle">Useful view of created vs approved requests for the same 14-day window.</p>
                        </div>
                        <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">Open Leave</a>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Total Requests</p>
                            <p class="mt-1 text-lg font-extrabold">{{ (int) ($adminCharts['leave']['totalRequests'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Approved</p>
                            <p class="mt-1 text-lg font-extrabold">{{ (int) ($adminCharts['leave']['approvedRequests'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg border p-3" style="border-color: var(--hr-line);">
                            <p class="ui-kpi-label">Approval Rate</p>
                            <p class="mt-1 text-lg font-extrabold">{{ number_format((float) ($adminCharts['leave']['approvalRate'] ?? 0), 1) }}%</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border p-3" style="border-color: var(--hr-line);">
                        <svg viewBox="0 0 100 100" class="w-full h-48" role="img" aria-label="Leave request trend chart for last 14 days">
                            <line x1="0" y1="75" x2="100" y2="75" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            <line x1="0" y1="50" x2="100" y2="50" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            <line x1="0" y1="25" x2="100" y2="25" stroke="rgb(148 163 184 / 0.28)" stroke-width="0.4"></line>
                            @if ($leaveRequestedPoints !== '')
                                <polyline points="{{ $leaveRequestedPoints }}" fill="none" stroke="#f59e0b" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            @endif
                            @if ($leaveApprovedPoints !== '')
                                <polyline points="{{ $leaveApprovedPoints }}" fill="none" stroke="#22c55e" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                            @endif
                            @foreach($trendDays as $index => $day)
                                @php
                                    $x = $trendCount > 1 ? ($index / ($trendCount - 1)) * 100 : 0;
                                    $requestedY = 100 - (min($leaveScaleMax, max(0, (int) ($day['leave_created'] ?? 0))) / $leaveScaleMax) * 100;
                                    $approvedY = 100 - (min($leaveScaleMax, max(0, (int) ($day['leave_approved'] ?? 0))) / $leaveScaleMax) * 100;
                                @endphp
                                <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($requestedY, 2, '.', '') }}" r="1.05" fill="#f59e0b"></circle>
                                <circle cx="{{ number_format($x, 2, '.', '') }}" cy="{{ number_format($approvedY, 2, '.', '') }}" r="1.05" fill="#22c55e"></circle>
                            @endforeach
                        </svg>
                        <div class="mt-2 grid grid-cols-7 md:grid-cols-14 gap-1 text-[10px] font-semibold" style="color: var(--hr-text-muted);">
                            @foreach($trendDays as $day)
                                <span class="text-center">{{ $day['label'] }}</span>
                            @endforeach
                        </div>
                        <div class="mt-2 flex items-center gap-4 text-xs" style="color: var(--hr-text-muted);">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: rgb(245 158 11 / 0.75);"></span>Requested</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" style="background: rgb(34 197 94 / 0.85);"></span>Approved</span>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        @include('dashboard.partials.user-activity', [
            'recentActivities' => $recentActivities,
            'activityTitle' => 'System Activity',
        ])
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Latest Employee Records</h3>
                <p class="ui-section-subtitle">Newest entries with status and module-ready profile details.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="ui-btn ui-btn-primary">Add Employee</a>
        </div>

        <div class="ui-table-wrap">
            <table class="ui-table">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Employment</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($latestEmployees as $employee)
                    @php
                        $profile = $employee->profile;
                        $status = $profile?->status ?? 'active';
                        $statusClass = match ($status) {
                            'inactive' => 'ui-status-amber',
                            'suspended' => 'ui-status-red',
                            default => 'ui-status-green',
                        };
                        $initials = strtoupper(substr($employee->name, 0, 1));
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="h-9 w-9 rounded-xl flex items-center justify-center text-xs font-extrabold" style="background: var(--hr-accent-soft); color: var(--hr-accent);">{{ $initials }}</span>
                                <div>
                                    <p class="font-semibold">{{ $employee->name }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $employee->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $profile?->department ?? 'Unassigned' }}</td>
                        <td>
                            <span class="ui-status-chip {{ $statusClass }}">{{ ucfirst($status) }}</span>
                        </td>
                        <td>{{ str((string) ($profile?->employment_type ?? 'full_time'))->replace('_', ' ')->title() }}</td>
                        <td>{{ $profile?->joined_on?->format('M d, Y') ?? 'N/A' }}</td>
                        <td><a href="{{ route('admin.users.edit', $employee) }}" class="ui-btn ui-btn-ghost">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ui-empty">No employee records found yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
