@extends('layouts.dashboard-modern')

@section('title', 'Admin Dashboard')
@section('page_heading', 'Admin Command Center')

@section('content')
    @php
        $dashboardFilterOptions = is_array($dashboardFilterOptions ?? null) ? $dashboardFilterOptions : [];
        $branchOptions = is_array($dashboardFilterOptions['branches'] ?? null) ? $dashboardFilterOptions['branches'] : [];
        $departmentOptions = is_array($dashboardFilterOptions['departments'] ?? null) ? $dashboardFilterOptions['departments'] : [];
        $dashboardFilterState = is_array($dashboardFilterState ?? null) ? $dashboardFilterState : [];
        $selectedBranchId = isset($dashboardFilterState['branchId']) && $dashboardFilterState['branchId'] !== null
            ? (string) $dashboardFilterState['branchId']
            : '';
        $selectedDepartmentId = isset($dashboardFilterState['departmentId']) && $dashboardFilterState['departmentId'] !== null
            ? (string) $dashboardFilterState['departmentId']
            : '';
    @endphp

    <section class="ui-hero">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">
            <div>
                <p class="ui-kpi-label">Platform Overview</p>
                @include('dashboard.partials.greeting-header', ['functionalTitle' => 'Admin Command Center'])
                <p class="ui-section-subtitle">Monitor users, employees, attendance, leave, payroll, departments, and branches from one dashboard.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full xl:w-auto">
                <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-primary">Manage Users</a>
                <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-ghost">Employee Directory</a>
                <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost">Run Payroll</a>
            </div>
        </div>
    </section>

    <section class="ui-section">
        <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] md:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Global Filter</p>
                <p class="text-xs font-semibold" style="color: var(--hr-text-muted);">Apply branch and department scope to the entire dashboard.</p>
            </div>
            <label class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">
                Branch
                <select name="branch_id" class="ui-select mt-1">
                    <option value="">All Branches</option>
                    @foreach($branchOptions as $branchOption)
                        <option value="{{ $branchOption['id'] ?? '' }}" @selected((string) ($branchOption['id'] ?? '') === $selectedBranchId)>
                            {{ $branchOption['name'] ?? 'Unnamed Branch' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">
                Department
                <select name="department_id" class="ui-select mt-1">
                    <option value="">All Departments</option>
                    @foreach($departmentOptions as $departmentOption)
                        <option value="{{ $departmentOption['id'] ?? '' }}" @selected((string) ($departmentOption['id'] ?? '') === $selectedDepartmentId)>
                            {{ $departmentOption['name'] ?? 'Unnamed Department' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-center gap-2 md:justify-end">
                <a href="{{ url()->current() }}" class="ui-btn ui-btn-ghost">Clear</a>
                <button type="submit" class="ui-btn ui-btn-primary">Apply</button>
            </div>
        </form>
    </section>

    <section class="ui-section">
        <div
            id="admin-dashboard-summary-cards-root"
            data-summary-endpoint="{{ route('api.dashboard.admin.summary') }}"
            data-branch-id="{{ $selectedBranchId }}"
            data-department-id="{{ $selectedDepartmentId }}"
        >
            <div class="rounded-2xl border p-4 text-sm font-semibold" style="background: var(--hr-surface-strong); border-color: var(--hr-line); color: var(--hr-text-muted);">
                Loading dashboard summary...
            </div>
        </div>
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

    <section>
        <div
            id="admin-dashboard-attendance-overview-root"
            data-endpoint="{{ route('api.dashboard.admin.attendance-overview') }}"
            data-absent-url="{{ route('modules.attendance.index', ['status' => 'absent', 'attendance_date' => now()->toDateString()]) }}"
            data-branch-id="{{ $selectedBranchId }}"
            data-department-id="{{ $selectedDepartmentId }}"
        >
            <div class="rounded-xl border p-4 text-sm font-semibold" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                Loading attendance overview...
            </div>
        </div>
    </section>

    <section class="mt-5">
        <div
            id="admin-dashboard-leave-overview-root"
            data-endpoint="{{ route('api.dashboard.admin.leave-overview') }}"
            data-branch-id="{{ $selectedBranchId }}"
            data-department-id="{{ $selectedDepartmentId }}"
        >
            <div class="rounded-xl border p-4 text-sm font-semibold" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                Loading leave overview...
            </div>
        </div>
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

    <section class="mt-5">
        @include('dashboard.partials.user-activity', [
            'recentActivities' => $recentActivities,
            'activityTitle' => 'System Activity',
        ])
    </section>
@endsection
