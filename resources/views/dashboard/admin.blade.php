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

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Module Command Deck</h3>
                    <p class="ui-section-subtitle">Quick access cards for all configured modules.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <a href="{{ route('admin.users.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-blue"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
                    <div><p class="font-semibold">Users</p><p class="ui-section-subtitle mt-1">Admin {{ $moduleStats['adminsTotal'] }} • HR {{ $moduleStats['hrTotal'] }}</p></div>
                </a>
                <a href="{{ route('modules.employees.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg></span>
                    <div><p class="font-semibold">Employees</p><p class="ui-section-subtitle mt-1">Total {{ $employeeStats['total'] }} • Active {{ $employeeStats['active'] }}</p></div>
                </a>
                <a href="{{ route('modules.attendance.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></span>
                    <div><p class="font-semibold">Attendance</p><p class="ui-section-subtitle mt-1">Marked today {{ $moduleStats['attendanceMarkedToday'] }}</p></div>
                </a>
                <a href="{{ route('modules.leave.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-amber"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
                    <div><p class="font-semibold">Leave</p><p class="ui-section-subtitle mt-1">Pending {{ $moduleStats['leavePending'] }}</p></div>
                </a>
                <a href="{{ route('modules.payroll.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
                    <div><p class="font-semibold">Payroll</p><p class="ui-section-subtitle mt-1">Net month {{ number_format((float) $moduleStats['payrollNetMonth'], 2) }}</p></div>
                </a>
                <a href="{{ route('modules.departments.index') }}" class="ui-tile-link">
                    <span class="ui-icon-chip ui-icon-pink"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path><path d="M9 10h6"></path><path d="M9 14h6"></path></svg></span>
                    <div><p class="font-semibold">Departments</p><p class="ui-section-subtitle mt-1">{{ $moduleStats['departmentsTotal'] }} configured • Branches {{ $moduleStats['branchesTotal'] }}</p></div>
                </a>
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
