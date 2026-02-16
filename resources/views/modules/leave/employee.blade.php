@extends('layouts.dashboard-modern')

@section('title', 'Leave')
@section('page_heading', 'My Leave')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please fix the leave request fields and submit again.</div>
    @endif

    <section class="ui-kpi-grid is-5">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Requests (Year)</p>
                    <p class="ui-kpi-value">{{ $stats['total'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Pending</p>
                    <p class="ui-kpi-value">{{ $stats['pending'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Approved Days</p>
                    <p class="ui-kpi-value">{{ number_format($stats['approvedDays'], 1) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Allowance</p>
                    <p class="ui-kpi-value">{{ number_format($stats['annualAllowance'], 1) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Remaining</p>
                    <p class="ui-kpi-value">{{ number_format($stats['remainingDays'], 1) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg></span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Apply Leave</h3>
                    <p class="ui-section-subtitle">Submit a new leave request for approval.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.leave.store') }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="leave_type" class="ui-kpi-label block mb-2">Leave Type</label>
                    <select id="leave_type" name="leave_type" class="ui-select">
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
                    <label for="day_type" class="ui-kpi-label block mb-2">Day Type</label>
                    <select id="day_type" name="day_type" class="ui-select">
                        <option value="full_day" {{ old('day_type', 'full_day') === 'full_day' ? 'selected' : '' }}>Full Day</option>
                        <option value="half_day" {{ old('day_type') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                    </select>
                    @error('day_type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="halfDaySessionWrap" class="{{ old('day_type') === 'half_day' ? '' : 'hidden' }}">
                    <label for="half_day_session" class="ui-kpi-label block mb-2">Half Day Slot</label>
                    <select id="half_day_session" name="half_day_session" class="ui-select">
                        <option value="">Select slot</option>
                        <option value="first_half" {{ old('half_day_session') === 'first_half' ? 'selected' : '' }}>First Half</option>
                        <option value="second_half" {{ old('half_day_session') === 'second_half' ? 'selected' : '' }}>Second Half</option>
                    </select>
                    @error('half_day_session')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="start_date" class="ui-kpi-label block mb-2">Start Date</label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" class="ui-input">
                    @error('start_date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_date" class="ui-kpi-label block mb-2">End Date</label>
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" class="ui-input">
                    @error('end_date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="reason" class="ui-kpi-label block mb-2">Reason</label>
                    <textarea id="reason" name="reason" rows="4" class="ui-textarea resize-y" placeholder="Please describe your leave reason...">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="ui-btn ui-btn-primary">Submit Request</button>
                </div>
            </form>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Upcoming Approved</h3>
                    <p class="ui-section-subtitle">Your approved future leave plans.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($upcomingApproved as $upcomingLeave)
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        @php
                            $isHalfDay = ($upcomingLeave->day_type ?? null) === 'half_day' || $upcomingLeave->leave_type === 'half_day';
                        @endphp
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold">
                                {{ str($upcomingLeave->leave_type)->replace('_', ' ')->title() }}
                                @if ($isHalfDay)
                                    (Half Day{{ $upcomingLeave->half_day_session ? ' - ' . str($upcomingLeave->half_day_session)->replace('_', ' ')->title() : '' }})
                                @endif
                            </p>
                            <span class="ui-status-chip ui-status-green">{{ number_format((float) $upcomingLeave->total_days, 1) }} days</span>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">
                            {{ $upcomingLeave->start_date?->format('M d, Y') }} to {{ $upcomingLeave->end_date?->format('M d, Y') }}
                        </p>
                    </li>
                @empty
                    <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        No upcoming approved leave.
                    </li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Leave History</h3>
                <p class="ui-section-subtitle">Track all your submitted leave requests.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.leave.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input
                type="number"
                min="2000"
                max="2100"
                name="year"
                value="{{ $filters['year'] }}"
                class="ui-input"
            >
            <select name="status" class="ui-select">
                <option value="">All Status</option>
                @foreach($statusOptions as $statusOption)
                    <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>
                        {{ str($statusOption)->replace('_', ' ')->title() }}
                    </option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 md:col-span-2">
                <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="ui-table-wrap">
            <table class="ui-table" style="min-width: 980px;">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Reviewer Note</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $leaveRequest)
                    @php
                        $statusClass = match ($leaveRequest->status) {
                            'approved' => 'ui-status-green',
                            'rejected' => 'ui-status-red',
                            'cancelled' => 'ui-status-slate',
                            default => 'ui-status-amber',
                        };
                        $isHalfDay = ($leaveRequest->day_type ?? null) === 'half_day' || $leaveRequest->leave_type === 'half_day';
                    @endphp
                    <tr>
                        <td>
                            {{ str($leaveRequest->leave_type)->replace('_', ' ')->title() }}
                            @if ($isHalfDay)
                                (Half Day{{ $leaveRequest->half_day_session ? ' - ' . str($leaveRequest->half_day_session)->replace('_', ' ')->title() : '' }})
                            @endif
                        </td>
                        <td>{{ $leaveRequest->start_date?->format('M d, Y') }} to {{ $leaveRequest->end_date?->format('M d, Y') }}</td>
                        <td>{{ number_format((float) $leaveRequest->total_days, 1) }}</td>
                        <td><span class="ui-status-chip {{ $statusClass }}">{{ str($leaveRequest->status)->replace('_', ' ')->title() }}</span></td>
                        <td class="max-w-[240px]">
                            <p>{{ $leaveRequest->reason }}</p>
                        </td>
                        <td>{{ $leaveRequest->review_note ?: 'N/A' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($leaveRequest->status === 'pending')
                                    <form method="POST" action="{{ route('modules.leave.cancel', $leaveRequest) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-btn !py-1.5 !text-xs" style="border-color: #dc2626; color: #dc2626;">
                                            Cancel
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs" style="color: var(--hr-text-muted);">No action</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="ui-empty">No leave requests for selected filters.</td>
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
            const dayType = document.getElementById('day_type');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const halfDayWrap = document.getElementById('halfDaySessionWrap');
            const halfDaySession = document.getElementById('half_day_session');

            if (!dayType || !startDate || !endDate || !halfDayWrap || !halfDaySession) {
                return;
            }

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
        })();
    </script>
@endpush
