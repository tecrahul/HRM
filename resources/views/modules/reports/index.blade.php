@extends('layouts.dashboard-modern')

@section('title', 'Reports')
@section('page_heading', 'Reports')

@section('content')
    @php
        $reportType = (string) ($filters['report_type'] ?? 'comprehensive');
        $showAttendance = in_array('attendance', $visibleSections, true);
        $showLeave = in_array('leave', $visibleSections, true);
        $showPayroll = in_array('payroll', $visibleSections, true);
        $showSummary = in_array('employee_summary', $visibleSections, true);
        $showActivity = in_array('activity', $visibleSections, true);
        $summaryMode = match ($reportType) {
            'attendance_monthly' => 'attendance',
            'leave_monthly' => 'leave',
            'payroll_monthly' => 'payroll',
            default => 'comprehensive',
        };
        $summaryColspan = match ($summaryMode) {
            'attendance' => 6,
            'leave' => 4,
            'payroll' => 6,
            default => 12,
        };
        $breakdownCount = ($showAttendance ? 1 : 0) + ($showLeave ? 1 : 0) + ($showPayroll ? 1 : 0);
        $breakdownGridClass = match ($breakdownCount) {
            1 => 'xl:grid-cols-1',
            2 => 'xl:grid-cols-2',
            default => 'xl:grid-cols-3',
        };
    @endphp

    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please review report filters and try again.</div>
    @endif

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Generate Reports</h3>
                <p class="ui-section-subtitle">{{ $reportTypeLabel }} report for {{ $periodLabel }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.reports.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-10 gap-3">
            <select name="report_type" class="ui-select md:col-span-2">
                @foreach($reportTypeOptions as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" {{ $reportType === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
                @endforeach
            </select>

            <input type="month" name="month" value="{{ $filters['month'] }}" class="ui-input" title="Reporting month">

            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="ui-input" title="From date">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="ui-input" title="To date">

            @if ($isManagement)
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Search employee"
                    class="ui-input md:col-span-2"
                >

                <select name="department" class="ui-select">
                    <option value="">All Departments</option>
                    @foreach($departmentOptions as $departmentOption)
                        <option value="{{ $departmentOption }}" {{ $filters['department'] === $departmentOption ? 'selected' : '' }}>
                            {{ $departmentOption }}
                        </option>
                    @endforeach
                </select>

                <select name="branch" class="ui-select">
                    <option value="">All Branches</option>
                    @foreach($branchOptions as $branchOption)
                        <option value="{{ $branchOption }}" {{ $filters['branch'] === $branchOption ? 'selected' : '' }}>
                            {{ $branchOption }}
                        </option>
                    @endforeach
                </select>

                <select name="employee_id" class="ui-select">
                    <option value="">All Employees</option>
                    @foreach($employeeOptions as $employeeOption)
                        <option value="{{ $employeeOption->id }}" {{ (int) $filters['employee_id'] === (int) $employeeOption->id ? 'selected' : '' }}>
                            {{ $employeeOption->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Generate</button>
                <button type="submit" name="export" value="csv" class="ui-btn">Export CSV</button>
                <a href="{{ route('modules.reports.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>
        </form>

        <p class="text-xs mt-3" style="color: var(--hr-text-muted);">
            Monthly report types use the selected month as the report window. For comprehensive reports, date range filters apply.
        </p>
    </section>

    <section class="ui-kpi-grid mt-5">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Employees In Scope</p>
                    <p class="ui-kpi-value">{{ $stats['employeeCount'] }}</p>
                    <p class="ui-kpi-meta">Period: {{ $stats['periodDays'] }} days</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>

        @if ($showAttendance)
            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Attendance Score</p>
                        <p class="ui-kpi-value">{{ number_format((float) $stats['attendanceRate'], 1) }}%</p>
                        <p class="ui-kpi-meta">{{ number_format((float) $stats['attendancePresentUnits'], 1) }} present units</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-sky">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.2 2"></path></svg>
                    </span>
                </div>
            </article>

            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Attendance Records</p>
                        <p class="ui-kpi-value">{{ $stats['attendanceRecords'] }}</p>
                        <p class="ui-kpi-meta">{{ number_format((float) $stats['attendanceWorkHours'], 1) }} total hours</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-violet">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg>
                    </span>
                </div>
            </article>
        @endif

        @if ($showLeave)
            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Leave Requests</p>
                        <p class="ui-kpi-value">{{ $stats['leaveRequests'] }}</p>
                        <p class="ui-kpi-meta">{{ $stats['leavePendingRequests'] }} pending</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-amber">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                    </span>
                </div>
            </article>

            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Approved Leave Days</p>
                        <p class="ui-kpi-value">{{ number_format((float) $stats['leaveApprovedDays'], 1) }}</p>
                        <p class="ui-kpi-meta">Selected period</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-pink">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path></svg>
                    </span>
                </div>
            </article>
        @endif

        @if ($showPayroll)
            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Payroll Entries</p>
                        <p class="ui-kpi-value">{{ $stats['payrollEntries'] }}</p>
                        <p class="ui-kpi-meta">{{ $stats['payrollPaidEntries'] }} paid</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-green">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                    </span>
                </div>
            </article>

            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Payroll Net</p>
                        <p class="ui-kpi-value">{{ number_format((float) $stats['payrollNet'], 2) }}</p>
                        <p class="ui-kpi-meta">Deductions: {{ number_format((float) $stats['payrollDeductions'], 2) }}</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-violet">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg>
                    </span>
                </div>
            </article>
        @endif

        @if ($showActivity)
            <article class="ui-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="ui-kpi-label">Activity Logged</p>
                        <p class="ui-kpi-value">{{ $stats['activityCount'] }}</p>
                        <p class="ui-kpi-meta">Within selected period</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-pink">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path></svg>
                    </span>
                </div>
            </article>
        @endif
    </section>

    @if ($showAttendance || $showLeave || $showPayroll)
        <section class="grid grid-cols-1 {{ $breakdownGridClass }} gap-5 mt-5">
            @if ($showAttendance)
                <article class="ui-section">
                    <div class="ui-section-head">
                        <div>
                            <h3 class="ui-section-title">Attendance Breakdown</h3>
                            <p class="ui-section-subtitle">Status distribution in selected period.</p>
                        </div>
                    </div>

                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse($attendanceStatusBreakdown as $item)
                            @php
                                $chipClass = match ((string) $item->status) {
                                    'present', 'remote' => 'ui-status-green',
                                    'half_day', 'on_leave' => 'ui-status-amber',
                                    'absent' => 'ui-status-red',
                                    default => 'ui-status-slate',
                                };
                            @endphp
                            <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                                    <span class="ui-status-chip {{ $chipClass }}">{{ $item->record_count }}</span>
                                </div>
                            </li>
                        @empty
                            <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No attendance records found.</li>
                        @endforelse
                    </ul>
                </article>
            @endif

            @if ($showLeave)
                <article class="ui-section">
                    <div class="ui-section-head">
                        <div>
                            <h3 class="ui-section-title">Leave Breakdown</h3>
                            <p class="ui-section-subtitle">Status and type distribution.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="ui-kpi-label mb-2">By Status</p>
                        <ul class="space-y-2 text-sm">
                            @forelse($leaveStatusBreakdown as $item)
                                @php
                                    $chipClass = match ((string) $item->status) {
                                        'approved' => 'ui-status-green',
                                        'pending' => 'ui-status-amber',
                                        'rejected' => 'ui-status-red',
                                        default => 'ui-status-slate',
                                    };
                                @endphp
                                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                                        <span class="ui-status-chip {{ $chipClass }}">{{ $item->request_count }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No leave records found.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="mt-4">
                        <p class="ui-kpi-label mb-2">By Type</p>
                        <ul class="space-y-2 text-sm">
                            @forelse($leaveTypeBreakdown as $item)
                                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ str($item->leave_type)->replace('_', ' ')->title() }}</span>
                                        <span class="ui-status-chip ui-status-slate">{{ $item->request_count }}</span>
                                    </div>
                                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Approved days: {{ number_format((float) $item->approved_days, 1) }}</p>
                                </li>
                            @empty
                                <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No leave type data found.</li>
                            @endforelse
                        </ul>
                    </div>
                </article>
            @endif

            @if ($showPayroll)
                <article class="ui-section">
                    <div class="ui-section-head">
                        <div>
                            <h3 class="ui-section-title">Payroll Breakdown</h3>
                            <p class="ui-section-subtitle">Payroll status and totals.</p>
                        </div>
                    </div>

                    @if (! $payrollEnabled)
                        <div class="ui-empty mt-4">Payroll tables are not available in this environment.</div>
                    @else
                        <ul class="mt-4 space-y-2 text-sm">
                            @forelse($payrollStatusBreakdown as $item)
                                @php
                                    $chipClass = match ((string) $item->status) {
                                        'paid' => 'ui-status-green',
                                        'processed' => 'ui-status-amber',
                                        'draft' => 'ui-status-slate',
                                        default => 'ui-status-slate',
                                    };
                                @endphp
                                <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                                        <span class="ui-status-chip {{ $chipClass }}">{{ $item->entry_count }}</span>
                                    </div>
                                </li>
                            @empty
                                <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No payroll records found.</li>
                            @endforelse
                        </ul>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <p class="ui-kpi-label">Net Salary</p>
                                <p class="text-lg font-extrabold mt-1">{{ number_format((float) $stats['payrollNet'], 2) }}</p>
                            </div>
                            <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <p class="ui-kpi-label">Deductions</p>
                                <p class="text-lg font-extrabold mt-1">{{ number_format((float) $stats['payrollDeductions'], 2) }}</p>
                            </div>
                        </div>
                    @endif
                </article>
            @endif
        </section>
    @endif

    @if ($showSummary)
        <section class="ui-section mt-5">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Employee Summary</h3>
                    <p class="ui-section-subtitle">
                        @if ($summaryMode === 'attendance')
                            Attendance-focused employee metrics.
                        @elseif ($summaryMode === 'leave')
                            Leave-focused employee metrics.
                        @elseif ($summaryMode === 'payroll')
                            Payroll-focused employee metrics.
                        @else
                            Combined attendance, leave, and payroll metrics by employee.
                        @endif
                    </p>
                </div>
            </div>

            <div class="ui-table-wrap">
                @if ($summaryMode === 'attendance')
                    <table class="ui-table" style="min-width: 980px;">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department / Branch</th>
                            <th>Marked Days</th>
                            <th>Present Units</th>
                            <th>Attendance %</th>
                            <th>Avg Hours/Day</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $row['name'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['email'] }}</p>
                                </td>
                                <td>
                                    <p>{{ $row['department'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['branch'] }}</p>
                                </td>
                                <td>{{ $row['marked_days'] }}</td>
                                <td>{{ number_format((float) $row['present_units'], 1) }}</td>
                                <td>{{ number_format((float) $row['attendance_percent'], 1) }}%</td>
                                <td>{{ number_format((float) $row['avg_hours'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $summaryColspan }}" class="ui-empty">No employee report data found for selected filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @elseif ($summaryMode === 'leave')
                    <table class="ui-table" style="min-width: 760px;">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department / Branch</th>
                            <th>Approved Leave Days</th>
                            <th>Pending Leave Requests</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $row['name'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['email'] }}</p>
                                </td>
                                <td>
                                    <p>{{ $row['department'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['branch'] }}</p>
                                </td>
                                <td>{{ number_format((float) $row['approved_leave_days'], 1) }}</td>
                                <td>{{ $row['pending_leave_requests'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $summaryColspan }}" class="ui-empty">No employee report data found for selected filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @elseif ($summaryMode === 'payroll')
                    <table class="ui-table" style="min-width: 980px;">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department / Branch</th>
                            <th>Payroll Entries</th>
                            <th>Paid Entries</th>
                            <th>Net Salary</th>
                            <th>Latest Payroll</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $payrollChipClass = match (strtolower((string) $row['latest_payroll_status'])) {
                                    'paid' => 'ui-status-green',
                                    'processed' => 'ui-status-amber',
                                    'draft' => 'ui-status-slate',
                                    default => 'ui-status-slate',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $row['name'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['email'] }}</p>
                                </td>
                                <td>
                                    <p>{{ $row['department'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['branch'] }}</p>
                                </td>
                                <td>{{ $row['payroll_entries'] }}</td>
                                <td>{{ $row['paid_entries'] }}</td>
                                <td>{{ number_format((float) $row['net_salary'], 2) }}</td>
                                <td>
                                    <p>{{ $row['latest_payroll_month'] }}</p>
                                    <span class="ui-status-chip {{ $payrollChipClass }}">{{ $row['latest_payroll_status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $summaryColspan }}" class="ui-empty">No employee report data found for selected filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="ui-table" style="min-width: 1380px;">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department / Branch</th>
                            <th>Marked Days</th>
                            <th>Present Units</th>
                            <th>Attendance %</th>
                            <th>Avg Hours/Day</th>
                            <th>Approved Leave Days</th>
                            <th>Pending Leaves</th>
                            <th>Payroll Entries</th>
                            <th>Paid Entries</th>
                            <th>Net Salary</th>
                            <th>Latest Payroll</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            @php
                                $payrollChipClass = match (strtolower((string) $row['latest_payroll_status'])) {
                                    'paid' => 'ui-status-green',
                                    'processed' => 'ui-status-amber',
                                    'draft' => 'ui-status-slate',
                                    default => 'ui-status-slate',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-semibold">{{ $row['name'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['email'] }}</p>
                                </td>
                                <td>
                                    <p>{{ $row['department'] }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">{{ $row['branch'] }}</p>
                                </td>
                                <td>{{ $row['marked_days'] }}</td>
                                <td>{{ number_format((float) $row['present_units'], 1) }}</td>
                                <td>{{ number_format((float) $row['attendance_percent'], 1) }}%</td>
                                <td>{{ number_format((float) $row['avg_hours'], 2) }}</td>
                                <td>{{ number_format((float) $row['approved_leave_days'], 1) }}</td>
                                <td>{{ $row['pending_leave_requests'] }}</td>
                                <td>{{ $row['payroll_entries'] }}</td>
                                <td>{{ $row['paid_entries'] }}</td>
                                <td>{{ number_format((float) $row['net_salary'], 2) }}</td>
                                <td>
                                    <p>{{ $row['latest_payroll_month'] }}</p>
                                    <span class="ui-status-chip {{ $payrollChipClass }}">{{ $row['latest_payroll_status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $summaryColspan }}" class="ui-empty">No employee report data found for selected filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        </section>
    @endif

    @if ($showActivity)
        <section class="ui-section mt-5">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Recent Activity</h3>
                    <p class="ui-section-subtitle">Latest tracked activities in selected period.</p>
                </div>
            </div>

            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Title</th>
                        <th>Actor</th>
                        <th>Meta</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->occurred_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                            <td>{{ $activity->title }}</td>
                            <td>{{ $activity->actor?->name ?? 'System' }}</td>
                            <td>{{ $activity->meta ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ui-empty">No activity available for this period.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
