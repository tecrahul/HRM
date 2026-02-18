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
        $attendanceScorePct = max(0, min(100, (float) $stats['attendanceRate']));
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
        <form method="GET" action="{{ route('modules.reports.index') }}" class="space-y-4">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Generate Reports</h3>
                    <p class="ui-section-subtitle">{{ $reportTypeLabel }} report for {{ $periodLabel }}</p>
                </div>

                <div class="w-full sm:w-auto">
                    <label for="report_month" class="ui-kpi-label block mb-2">Report Month</label>
                    <input
                        id="report_month"
                        type="month"
                        name="month"
                        value="{{ $filters['month'] }}"
                        class="ui-input sm:min-w-[220px]"
                        title="Reporting month"
                    >
                </div>
            </div>

            <div class="rounded-xl border p-4 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Core Filters</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="report_type" class="ui-kpi-label block mb-2">Report Type</label>
                        <select id="report_type" name="report_type" class="ui-select">
                            @foreach($reportTypeOptions as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" {{ $reportType === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isManagement)
                        <div>
                            <label for="report_q" class="ui-kpi-label block mb-2">Search Keyword</label>
                            <input
                                id="report_q"
                                type="text"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="Name, email, branch, or department"
                                class="ui-input"
                            >
                        </div>

                        <div>
                            <label for="report_department" class="ui-kpi-label block mb-2">Department</label>
                            <select id="report_department" name="department" class="ui-select">
                                <option value="">All Departments</option>
                                @foreach($departmentOptions as $departmentOption)
                                    <option value="{{ $departmentOption }}" {{ $filters['department'] === $departmentOption ? 'selected' : '' }}>
                                        {{ $departmentOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                @if ($isManagement)
                    <p class="ui-kpi-label pt-1">People Filters</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label for="report_branch" class="ui-kpi-label block mb-2">Branch</label>
                            <select id="report_branch" name="branch" class="ui-select">
                                <option value="">All Branches</option>
                                @foreach($branchOptions as $branchOption)
                                    <option value="{{ $branchOption }}" {{ $filters['branch'] === $branchOption ? 'selected' : '' }}>
                                        {{ $branchOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="report_employee_id" class="ui-kpi-label block mb-2">Employee</label>
                            <div
                                data-employee-autocomplete-root
                                data-api-url="{{ route('api.employees.search') }}"
                                data-name="employee_id"
                                data-input-id="report_employee_id"
                                data-selected='@json($selectedReportEmployee)'
                            ></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border p-3 flex flex-wrap items-center gap-2" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <button type="submit" class="ui-btn ui-btn-primary">Generate Report</button>
                <button type="submit" name="export" value="csv" class="ui-btn">Export CSV</button>
                <a href="{{ route('modules.reports.index') }}" class="ui-btn ui-btn-ghost">Clear Filters</a>
            </div>
        </form>

        <p class="text-xs mt-2" style="color: var(--hr-text-muted);">
            Choose a report type and month, then optionally narrow by people filters.
        </p>
    </section>

    <section class="ui-kpi-grid is-3 mt-5">
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
                        <p class="ui-kpi-value">{{ number_format($attendanceScorePct, 1) }}%</p>
                        <p class="ui-kpi-meta">{{ number_format((float) $stats['attendancePresentUnits'], 1) }} present units</p>
                    </div>
                    <div
                        class="h-14 w-14 rounded-full p-1.5 flex items-center justify-center"
                        style="background: conic-gradient(#0284c7 0 {{ $attendanceScorePct }}%, rgb(148 163 184 / 0.2) {{ $attendanceScorePct }}% 100%);"
                        aria-label="Attendance score pie chart"
                    >
                        <div class="h-full w-full rounded-full flex items-center justify-center text-[11px] font-extrabold" style="background: var(--hr-surface); color: var(--hr-text-main);">
                            {{ number_format($attendanceScorePct, 0) }}%
                        </div>
                    </div>
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

    </section>

    @if ($showAttendance || $showLeave || $showPayroll)
        <section class="grid grid-cols-1 {{ $breakdownGridClass }} gap-5 mt-5">
            @if ($showAttendance)
            @php
                $attendanceSegments = collect($attendanceStatusBreakdown ?? [])
                    ->map(fn ($item) => [
                        'status' => (string) $item->status,
                        'label' => str($item->status)->replace('_', ' ')->title(),
                        'count' => max(0, (int) $item->record_count),
                        'color' => match ((string) $item->status) {
                            'present' => '#16a34a',
                            'remote' => '#0284c7',
                            'half_day' => '#d97706',
                            'on_leave' => '#f97316',
                            'late' => '#c026d3',
                            'absent' => '#dc2626',
                            default => '#64748b',
                        },
                    ])
                    ->values();
                $attendanceTotal = $attendanceSegments->sum('count');
                $gradientSegments = [];
                $cursor = 0.0;

                if ($attendanceTotal > 0) {
                    foreach ($attendanceSegments as $segment) {
                        if ($segment['count'] <= 0) {
                            continue;
                        }

                        $slice = ($segment['count'] / $attendanceTotal) * 100;
                        $start = $cursor;
                        $end = $cursor + $slice;
                        $gradientSegments[] = sprintf(
                            '%s %s%% %s%%',
                            $segment['color'],
                            number_format($start, 2, '.', ''),
                            number_format($end, 2, '.', '')
                        );
                        $cursor = $end;
                    }
                }

                $attendancePieBackground = $attendanceTotal > 0 && count($gradientSegments) > 0
                    ? implode(', ', $gradientSegments)
                    : 'rgb(148 163 184 / 0.2) 0% 100%';
            @endphp

            <article class="ui-section">
                <div class="ui-section-head">
                    <div>
                        <h3 class="ui-section-title">Attendance Breakdown</h3>
                        <p class="ui-section-subtitle">Status distribution in selected period.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-6 lg:grid-cols-[220px,1fr] items-start">
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="h-44 w-44 rounded-full p-2"
                            style="background: conic-gradient({{ $attendancePieBackground }});"
                            aria-label="Attendance pie chart"
                        >
                            <div class="h-full w-full rounded-full flex items-center justify-center" style="background: var(--hr-surface);">
                                <div class="text-center">
                                    <p class="text-lg font-extrabold">{{ $attendanceTotal }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">records</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-[var(--hr-text-muted)]">Pie chart colors match the list on the right.</p>
                    </div>

                    <div class="space-y-2 text-sm">
                        @if ($attendanceSegments->isEmpty())
                            <p class="text-xs text-[var(--hr-text-muted)]">No attendance records found.</p>
                        @else
                            <p class="text-xs font-semibold text-[var(--hr-text-muted)]">Status legend and counts</p>
                            <ul class="space-y-2">
                                @foreach($attendanceSegments as $segment)
                                    <li class="flex items-center justify-between py-1">
                                        <div class="flex items-center gap-2 font-semibold">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $segment['color'] }};"></span>
                                            {{ $segment['label'] }}
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <span>{{ $segment['count'] }}</span>
                                            <span class="text-[0.65rem]" style="color: var(--hr-text-muted);">
                                                {{ $attendanceTotal > 0 ? number_format(($segment['count'] / $attendanceTotal) * 100, 1) : '0.0' }}%
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
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

@endsection
