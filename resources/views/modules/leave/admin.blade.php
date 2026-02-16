@extends('layouts.dashboard-modern')

@section('title', 'Leave')
@section('page_heading', 'Leave Management')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please review the form errors and try again.</div>
    @endif

    <section class="ui-kpi-grid is-5">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Total Requests</p>
                    <p class="ui-kpi-value">{{ $stats['total'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Pending</p>
                    <p class="ui-kpi-value">{{ $stats['pending'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Approved</p>
                    <p class="ui-kpi-value">{{ $stats['approved'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Rejected</p>
                    <p class="ui-kpi-value">{{ $stats['rejected'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-pink">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Approved Days (Month)</p>
                    <p class="ui-kpi-value">{{ number_format($stats['approvedDaysThisMonth'], 1) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Assign Leave</h3>
                    <p class="ui-section-subtitle">Create leave directly for an employee (auto-approved).</p>
                </div>
            </div>

            <form id="assignLeaveForm" method="POST" action="{{ route('modules.leave.store') }}" class="mt-4 grid grid-cols-1 gap-3">
                @csrf

                <div>
                    @php
                        $selectedAssignEmployee = $employees->firstWhere('id', (int) old('user_id'));
                        $assignEmployeeLookupOptions = $employees
                            ->map(function ($employee) {
                                return [
                                    'id' => (string) $employee->id,
                                    'name' => (string) $employee->name,
                                    'email' => (string) $employee->email,
                                ];
                            })
                            ->values()
                            ->all();
                    @endphp
                    <label for="assign_employee_lookup" class="ui-kpi-label block mb-2">Employee</label>
                    <input
                        id="assign_employee_lookup"
                        type="text"
                        list="assign_employee_options"
                        class="ui-input"
                        placeholder="Type name or employee ID"
                        autocomplete="off"
                        value="{{ $selectedAssignEmployee ? ($selectedAssignEmployee->name . ' (ID: ' . $selectedAssignEmployee->id . ')') : '' }}"
                    >
                    <input id="assign_user_id" type="hidden" name="user_id" value="{{ old('user_id') }}">
                    <datalist id="assign_employee_options">
                        @foreach($employees as $employee)
                            <option value="{{ $employee->name }} (ID: {{ $employee->id }})">{{ $employee->email }}</option>
                        @endforeach
                    </datalist>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Search by starting letters of name or employee ID.</p>
                    @error('user_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="assign_leave_type" class="ui-kpi-label block mb-2">Leave Type</label>
                    <select id="assign_leave_type" name="leave_type" class="ui-select">
                        <option value="">Select type</option>
                        @foreach($leaveTypeOptions as $leaveTypeOption)
                            <option value="{{ $leaveTypeOption }}" {{ old('leave_type') === $leaveTypeOption ? 'selected' : '' }}>
                                {{ str($leaveTypeOption)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                    @error('leave_type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="assign_day_type" class="ui-kpi-label block mb-2">Day Type</label>
                    <select id="assign_day_type" name="day_type" class="ui-select">
                        <option value="full_day" {{ old('day_type', 'full_day') === 'full_day' ? 'selected' : '' }}>Full Day</option>
                        <option value="half_day" {{ old('day_type') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                    </select>
                    @error('day_type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="assignHalfDaySessionWrap" class="{{ old('day_type') === 'half_day' ? '' : 'hidden' }}">
                    <label for="assign_half_day_session" class="ui-kpi-label block mb-2">Half Day Slot</label>
                    <select id="assign_half_day_session" name="half_day_session" class="ui-select">
                        <option value="">Select slot</option>
                        <option value="first_half" {{ old('half_day_session') === 'first_half' ? 'selected' : '' }}>First Half</option>
                        <option value="second_half" {{ old('half_day_session') === 'second_half' ? 'selected' : '' }}>Second Half</option>
                    </select>
                    @error('half_day_session')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="assign_start_date" class="ui-kpi-label block mb-2">Start Date</label>
                        <input id="assign_start_date" name="start_date" type="date" value="{{ old('start_date') }}" class="ui-input">
                        @error('start_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="assign_end_date" class="ui-kpi-label block mb-2">End Date</label>
                        <input id="assign_end_date" name="end_date" type="date" value="{{ old('end_date') }}" class="ui-input">
                        @error('end_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="assign_reason" class="ui-kpi-label block mb-2">Reason</label>
                    <textarea id="assign_reason" name="reason" rows="3" class="ui-textarea" placeholder="Provide leave reason">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="assign_review_note" class="ui-kpi-label block mb-2">Assignment Note (Optional)</label>
                    <input id="assign_review_note" name="assign_note" type="text" value="{{ old('assign_note') }}" class="ui-input" placeholder="Internal note">
                    @error('assign_note')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="ui-btn ui-btn-primary">Assign Leave</button>
            </form>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Pending Approvals</h3>
                    <p class="ui-section-subtitle">Requests that need action first.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($pendingApprovals as $pendingRequest)
                    @php
                        $isHalfDay = ($pendingRequest->day_type ?? null) === 'half_day' || $pendingRequest->leave_type === 'half_day';
                    @endphp
                    <li>
                        <a
                            href="{{ route('modules.leave.review.form', $pendingRequest) }}"
                            class="block rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
                            style="border-color: var(--hr-line); background: var(--hr-surface-strong);"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold">{{ $pendingRequest->user?->name }}</p>
                                <span class="ui-status-chip ui-status-amber">Pending</span>
                            </div>
                            <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">
                                {{ str($pendingRequest->leave_type)->replace('_', ' ')->title() }}
                                @if ($isHalfDay)
                                    (Half Day{{ $pendingRequest->half_day_session ? ' - ' . str($pendingRequest->half_day_session)->replace('_', ' ')->title() : '' }})
                                @endif
                                •
                                {{ $pendingRequest->start_date?->format('M d') }} to {{ $pendingRequest->end_date?->format('M d') }}
                            </p>
                            <p class="mt-2 text-xs font-semibold" style="color: var(--hr-primary);">Open review form</p>
                        </a>
                    </li>
                @empty
                    <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No pending approvals right now.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Leave Directory</h3>
                <p class="ui-section-subtitle">Review, approve, and audit employee leave requests.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.leave.index') }}" class="mt-4 rounded-xl border p-4 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
            <p class="ui-kpi-label">Directory Filters</p>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div>
                    <label for="filter_q" class="ui-kpi-label block mb-2">Search</label>
                    <input
                        id="filter_q"
                        type="text"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Employee, reason, department"
                        class="ui-input"
                    >
                </div>

                <div>
                    <label for="filter_employee_id" class="ui-kpi-label block mb-2">Employee</label>
                    <select id="filter_employee_id" name="employee_id" class="ui-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) $filters['employee_id'] === (string) $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_department" class="ui-kpi-label block mb-2">Department</label>
                    <select id="filter_department" name="department" class="ui-select">
                        <option value="">All Departments</option>
                        @foreach($departmentOptions as $departmentOption)
                            <option value="{{ $departmentOption }}" {{ $filters['department'] === $departmentOption ? 'selected' : '' }}>
                                {{ $departmentOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_branch" class="ui-kpi-label block mb-2">Branch</label>
                    <select id="filter_branch" name="branch" class="ui-select">
                        <option value="">All Branches</option>
                        @foreach($branchOptions as $branchOption)
                            <option value="{{ $branchOption }}" {{ $filters['branch'] === $branchOption ? 'selected' : '' }}>
                                {{ $branchOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div>
                    <label for="filter_leave_type" class="ui-kpi-label block mb-2">Leave Type</label>
                    <select id="filter_leave_type" name="leave_type" class="ui-select">
                        <option value="">All Types</option>
                        @foreach($leaveTypeOptions as $leaveTypeOption)
                            <option value="{{ $leaveTypeOption }}" {{ $filters['leave_type'] === $leaveTypeOption ? 'selected' : '' }}>
                                {{ str($leaveTypeOption)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="ui-kpi-label block mb-2">Status</label>
                    <select id="filter_status" name="status" class="ui-select">
                        <option value="">All Status</option>
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>
                                {{ str($statusOption)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_date_from" class="ui-kpi-label block mb-2">From Date</label>
                    <input id="filter_date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="ui-input">
                </div>

                <div>
                    <label for="filter_date_to" class="ui-kpi-label block mb-2">To Date</label>
                    <input id="filter_date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="ui-input">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Apply Filters</button>
                <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="ui-table-wrap">
            <table class="ui-table" style="min-width: 1250px;">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Reviewer</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $leaveRequest)
                    @php
                        $profile = $leaveRequest->user?->profile;
                        $statusClass = match ($leaveRequest->status) {
                            'approved' => 'ui-status-green',
                            'rejected' => 'ui-status-red',
                            'cancelled' => 'ui-status-slate',
                            default => 'ui-status-amber',
                        };
                    @endphp
                    <tr>
                        <td>
                            <p class="font-semibold">{{ $leaveRequest->user?->name }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $profile?->department ?? 'No Department' }} • {{ $profile?->branch ?? 'No Branch' }}</p>
                        </td>
                        <td>
                            {{ str($leaveRequest->leave_type)->replace('_', ' ')->title() }}
                            @php
                                $isHalfDay = ($leaveRequest->day_type ?? null) === 'half_day' || $leaveRequest->leave_type === 'half_day';
                            @endphp
                            @if ($isHalfDay)
                                (Half Day{{ $leaveRequest->half_day_session ? ' - ' . str($leaveRequest->half_day_session)->replace('_', ' ')->title() : '' }})
                            @endif
                        </td>
                        <td>
                            {{ $leaveRequest->start_date?->format('M d, Y') }}
                            <span style="color: var(--hr-text-muted);">to</span>
                            {{ $leaveRequest->end_date?->format('M d, Y') }}
                        </td>
                        <td>{{ number_format((float) $leaveRequest->total_days, 1) }}</td>
                        <td>
                            <span class="ui-status-chip {{ $statusClass }}">{{ str($leaveRequest->status)->replace('_', ' ')->title() }}</span>
                        </td>
                        <td class="max-w-[280px]">
                            <p>{{ $leaveRequest->reason }}</p>
                            @if (! blank($leaveRequest->review_note))
                                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Note: {{ $leaveRequest->review_note }}</p>
                            @endif
                        </td>
                        <td>
                            <p>{{ $leaveRequest->reviewer?->name ?? 'Pending' }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $leaveRequest->reviewed_at?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                        </td>
                        <td>
                            @if ($leaveRequest->status === 'pending')
                                <div class="text-right">
                                    <a href="{{ route('modules.leave.review.form', $leaveRequest) }}" class="ui-btn !py-1.5 !text-xs ui-btn-primary">Review Request</a>
                                </div>
                            @else
                                <div class="text-right text-xs" style="color: var(--hr-text-muted);">No action</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="ui-empty">No leave requests found for selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const assignForm = document.getElementById('assignLeaveForm');
            const employeeLookup = document.getElementById('assign_employee_lookup');
            const assignUserId = document.getElementById('assign_user_id');
            const employeeOptions = @json($assignEmployeeLookupOptions);

            if (assignForm && employeeLookup && assignUserId) {
                const preparedOptions = employeeOptions.map((option) => {
                    const label = `${option.name} (ID: ${option.id})`;
                    return {
                        ...option,
                        label,
                        idLower: option.id.toLowerCase(),
                        nameLower: option.name.toLowerCase(),
                        emailLower: option.email.toLowerCase(),
                        labelLower: label.toLowerCase(),
                    };
                });

                const resolveEmployee = (rawValue) => {
                    const value = String(rawValue || '').trim().toLowerCase();
                    if (value === '') {
                        return null;
                    }

                    const exactMatch = preparedOptions.find((option) =>
                        option.idLower === value
                        || option.nameLower === value
                        || option.emailLower === value
                        || option.labelLower === value
                    );
                    if (exactMatch) {
                        return exactMatch;
                    }

                    const prefixMatches = preparedOptions.filter((option) =>
                        option.nameLower.startsWith(value) || option.idLower.startsWith(value)
                    );

                    return prefixMatches.length === 1 ? prefixMatches[0] : null;
                };

                const syncEmployeeSelection = (isSubmit = false) => {
                    const typedValue = employeeLookup.value.trim();
                    const matchedEmployee = resolveEmployee(typedValue);

                    if (matchedEmployee) {
                        assignUserId.value = matchedEmployee.id;
                        employeeLookup.setCustomValidity('');
                        return true;
                    }

                    assignUserId.value = '';
                    if (!isSubmit) {
                        employeeLookup.setCustomValidity('');
                        return false;
                    }

                    employeeLookup.setCustomValidity(
                        typedValue === ''
                            ? 'Employee selection is required.'
                            : 'Select a valid employee by name or employee ID.'
                    );
                    employeeLookup.reportValidity();
                    return false;
                };

                employeeLookup.addEventListener('input', () => {
                    employeeLookup.setCustomValidity('');
                    if (employeeLookup.value.trim() === '') {
                        assignUserId.value = '';
                        return;
                    }
                    syncEmployeeSelection(false);
                });

                employeeLookup.addEventListener('change', () => syncEmployeeSelection(false));
                employeeLookup.addEventListener('blur', () => syncEmployeeSelection(false));
                assignForm.addEventListener('submit', (event) => {
                    if (!syncEmployeeSelection(true)) {
                        event.preventDefault();
                    }
                });
            }

            const dayType = document.getElementById('assign_day_type');
            const startDate = document.getElementById('assign_start_date');
            const endDate = document.getElementById('assign_end_date');
            const halfDayWrap = document.getElementById('assignHalfDaySessionWrap');
            const halfDaySession = document.getElementById('assign_half_day_session');

            if (dayType && startDate && endDate && halfDayWrap && halfDaySession) {
                const syncHalfDayState = () => {
                    const isHalfDay = dayType.value === 'half_day';
                    halfDayWrap.classList.toggle('hidden', !isHalfDay);

                    if (isHalfDay) {
                        if (startDate.value !== '') {
                            endDate.value = startDate.value;
                        }
                        endDate.readOnly = true;
                    } else {
                        endDate.readOnly = false;
                        halfDaySession.value = '';
                    }
                };

                dayType.addEventListener('change', syncHalfDayState);
                startDate.addEventListener('change', () => {
                    if (dayType.value === 'half_day') {
                        endDate.value = startDate.value;
                    }
                });

                syncHalfDayState();
            }
        })();
    </script>
@endpush
