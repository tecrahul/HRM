@extends('layouts.dashboard-modern')

@section('title', 'Employee Dashboard')
@section('page_heading', 'My Workspace')

@section('content')
    @php
        $attendanceScore = max(0, min(100, (float) $employeeSnapshot['attendanceScore']));
        $annualAllowance = (float) $employeeSnapshot['approvedLeaveDays'] + (float) $employeeSnapshot['remainingLeave'];
        $leaveUsedPct = $annualAllowance > 0
            ? round(min(100, ((float) $employeeSnapshot['approvedLeaveDays'] / $annualAllowance) * 100), 1)
            : 0.0;

        $payrollStatus = (string) $employeeSnapshot['latestPayrollStatus'];
        $payrollReadinessPct = match ($payrollStatus) {
            'paid' => 100.0,
            'processed' => 75.0,
            'draft' => 45.0,
            default => 0.0,
        };

        $pendingLeaves = max(0, (int) $employeeSnapshot['pendingLeaves']);
        $leavePendingLoadPct = min(100, (float) ($pendingLeaves * 20));

        $pendingPayslips = max(0, (int) $employeeSnapshot['payrollPending']);
        $payslipPendingLoadPct = min(100, (float) ($pendingPayslips * 25));
    @endphp

    <section class="ui-hero">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div>
                <p class="ui-kpi-label">Daily Command</p>
                <h2 class="mt-2 text-2xl md:text-3xl font-extrabold">Everything You Need In One Place</h2>
                <p class="ui-section-subtitle">Track attendance, leave, payroll, and profile updates from your workspace.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto">
                <a href="{{ route('modules.attendance.index') }}" class="ui-btn ui-btn-primary">Attendance</a>
                <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">Apply Leave</a>
                <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost">Payslips</a>
            </div>
        </div>
    </section>

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Attendance Score</p>
                    <p class="ui-kpi-value">{{ number_format((float) $employeeSnapshot['attendanceScore'], 1) }}%</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Present units {{ number_format((float) $employeeSnapshot['presentUnits'], 1) }} / {{ $employeeSnapshot['monthDays'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Leave Balance</p>
                    <p class="ui-kpi-value">{{ number_format((float) $employeeSnapshot['remainingLeave'], 1) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Pending requests {{ $employeeSnapshot['pendingLeaves'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Latest Payroll</p>
                    <p class="ui-kpi-value">{{ number_format((float) $employeeSnapshot['latestPayrollNet'], 2) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Status {{ str($employeeSnapshot['latestPayrollStatus'])->replace('_', ' ')->title() }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Net Paid (Year)</p>
                    <p class="ui-kpi-value">{{ number_format((float) $employeeSnapshot['thisYearNet'], 2) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg></span>
            </div>
            <p class="ui-kpi-meta">Pending payslips {{ $employeeSnapshot['payrollPending'] }}</p>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">My Progress Rings</h3>
                    <p class="ui-section-subtitle">Attendance and leave usage at a glance.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-xl border p-4 flex flex-col items-center text-center" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="h-28 w-28 rounded-full p-2" style="background: conic-gradient(#0284c7 0 {{ $attendanceScore }}%, rgb(148 163 184 / 0.2) {{ $attendanceScore }}% 100%);">
                        <div class="h-full w-full rounded-full flex items-center justify-center" style="background: var(--hr-surface-strong);">
                            <p class="text-lg font-extrabold">{{ number_format($attendanceScore, 1) }}%</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm font-semibold">Attendance Score</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ number_format((float) $employeeSnapshot['presentUnits'], 1) }} present units this month</p>
                </div>

                <div class="rounded-xl border p-4 flex flex-col items-center text-center" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="h-28 w-28 rounded-full p-2" style="background: conic-gradient(#d97706 0 {{ $leaveUsedPct }}%, rgb(148 163 184 / 0.2) {{ $leaveUsedPct }}% 100%);">
                        <div class="h-full w-full rounded-full flex items-center justify-center" style="background: var(--hr-surface-strong);">
                            <p class="text-lg font-extrabold">{{ number_format($leaveUsedPct, 1) }}%</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm font-semibold">Annual Leave Used</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ number_format((float) $employeeSnapshot['remainingLeave'], 1) }} days remaining</p>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Request And Payroll Signal</h3>
                    <p class="ui-section-subtitle">Current queue and payroll readiness indicators.</p>
                </div>
            </div>

            <div class="mt-4 space-y-4 text-sm">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Latest payroll readiness</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ number_format($payrollReadinessPct, 1) }}%</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $payrollReadinessPct }}%; background: #7c3aed;"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Pending leave load</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ $pendingLeaves }} request(s)</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $leavePendingLoadPct }}%; background: #d97706;"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <p class="font-semibold">Pending payslip load</p>
                        <p class="text-xs font-bold" style="color: var(--hr-text-muted);">{{ $pendingPayslips }} cycle(s)</p>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background: rgb(148 163 184 / 0.16);">
                        <div class="h-full rounded-full" style="width: {{ $payslipPendingLoadPct }}%; background: #15803d;"></div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Quick Actions</h3>
                    <p class="ui-section-subtitle">Open the right module without switching around the sidebar.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <a href="{{ route('modules.attendance.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
                    <div><p class="font-semibold">Attendance</p><p class="ui-section-subtitle mt-1">Check in/out and history</p></div>
                </a>

                <a href="{{ route('modules.leave.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-amber"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
                    <div><p class="font-semibold">Leave</p><p class="ui-section-subtitle mt-1">Apply and track approvals</p></div>
                </a>

                <a href="{{ route('modules.payroll.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
                    <div><p class="font-semibold">Payroll</p><p class="ui-section-subtitle mt-1">View payslips and net salary</p></div>
                </a>

                <a href="{{ route('profile.edit') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-pink"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg></span>
                    <div><p class="font-semibold">My Profile</p><p class="ui-section-subtitle mt-1">Update personal and work details</p></div>
                </a>
            </div>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Upcoming Snapshot</h3>
                    <p class="ui-section-subtitle">Short view of what is next in your account.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="font-semibold">Next Approved Leave</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                        {{ $employeeSnapshot['nextApprovedLeaveDate'] ? $employeeSnapshot['nextApprovedLeaveDate']->format('M d, Y') : 'No upcoming approved leave' }}
                    </p>
                </li>
                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="font-semibold">Current Leave Usage</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Approved this year {{ number_format((float) $employeeSnapshot['approvedLeaveDays'], 1) }} days</p>
                </li>
                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="font-semibold">Payroll Status</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ str($employeeSnapshot['latestPayrollStatus'])->replace('_', ' ')->title() }}</p>
                </li>
            </ul>
        </article>
    </section>
@endsection
