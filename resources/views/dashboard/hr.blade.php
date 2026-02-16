@extends('layouts.dashboard-modern')

@section('title', 'HR Dashboard')
@section('page_heading', 'HR Operations Dashboard')

@section('content')
    @php
        $attentionEmployees = $latestEmployees
            ->filter(fn ($employee) => in_array($employee->profile?->status, ['inactive', 'suspended'], true))
            ->take(5);
    @endphp

    <section class="ui-hero">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div>
                <p class="ui-kpi-label">HR Mission Board</p>
                <h2 class="mt-2 text-2xl md:text-3xl font-extrabold">People, Attendance, Leave, Payroll</h2>
                <p class="ui-section-subtitle">Run daily HR workflows with live module metrics and quick actions.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto">
                <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-primary">Employees</a>
                <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">Leave Queue</a>
                <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost">Payroll Desk</a>
            </div>
        </div>
    </section>

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Employees</p>
                    <p class="ui-kpi-value">{{ $employeeStats['total'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </span>
            </div>
            <p class="ui-kpi-meta">Active {{ $employeeStats['active'] }} • New 30d {{ $employeeStats['newJoiners'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Attendance Today</p>
                    <p class="ui-kpi-value">{{ $moduleStats['attendanceMarkedToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
            </div>
            <p class="ui-kpi-meta">Present/remote/half-day {{ $moduleStats['attendancePresentToday'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Leave Queue</p>
                    <p class="ui-kpi-value">{{ $moduleStats['leavePending'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                </span>
            </div>
            <p class="ui-kpi-meta">Approved this month {{ $moduleStats['leaveApprovedMonth'] }}</p>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Payroll Month</p>
                    <p class="ui-kpi-value">{{ $moduleStats['payrollGeneratedMonth'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                </span>
            </div>
            <p class="ui-kpi-meta">Paid {{ $moduleStats['payrollPaidMonth'] }} • Pending {{ $moduleStats['payrollPendingMonth'] }}</p>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">HR Priority Queue</h3>
                    <p class="ui-section-subtitle">Employees who need review because of profile status.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($attentionEmployees as $employee)
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $employee->name }}</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $employee->profile?->department ?? 'Unassigned' }} • {{ ucfirst((string) ($employee->profile?->status ?? 'active')) }}</p>
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $employee->email }}</p>
                            </div>
                            <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-ghost">Review</a>
                        </div>
                    </li>
                @empty
                    <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No urgent employee follow-up items right now.</li>
                @endforelse
            </ul>
        </article>

        @include('dashboard.partials.user-activity', [
            'recentActivities' => $recentActivities,
            'activityTitle' => 'Module Activity',
        ])
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">HR Modules</h3>
                <p class="ui-section-subtitle">Use these modules to complete operational tasks quickly.</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <a href="{{ route('modules.employees.index') }}" class="ui-tile-link">
                <span class="ui-icon-chip ui-icon-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg></span>
                <div><p class="font-semibold">Employees</p><p class="ui-section-subtitle mt-1">Directory and profiles</p></div>
            </a>
            <a href="{{ route('modules.attendance.index') }}" class="ui-tile-link">
                <span class="ui-icon-chip ui-icon-sky"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
                <div><p class="font-semibold">Attendance</p><p class="ui-section-subtitle mt-1">Mark and audit records</p></div>
            </a>
            <a href="{{ route('modules.leave.index') }}" class="ui-tile-link">
                <span class="ui-icon-chip ui-icon-amber"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
                <div><p class="font-semibold">Leave</p><p class="ui-section-subtitle mt-1">Approvals and tracking</p></div>
            </a>
            <a href="{{ route('modules.payroll.index') }}" class="ui-tile-link">
                <span class="ui-icon-chip ui-icon-violet"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
                <div><p class="font-semibold">Payroll</p><p class="ui-section-subtitle mt-1">Payslip generation</p></div>
            </a>
        </div>
    </section>
@endsection
