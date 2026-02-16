@extends('layouts.dashboard-modern')

@section('title', 'Attendance')
@section('page_heading', 'My Attendance')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $todayStatus = $todayRecord?->status ? str($todayRecord->status)->replace('_', ' ')->title() : 'Not Marked';
        $canCheckIn = ! $todayRecord || $todayRecord->check_in_at === null;
        $canCheckOut = $todayRecord && $todayRecord->check_in_at !== null && $todayRecord->check_out_at === null;
    @endphp

    <section class="ui-hero">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-3">
                <span class="ui-icon-chip ui-icon-sky">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
                <div>
                    <p class="ui-kpi-label">Today</p>
                    <h3 class="text-2xl font-extrabold mt-1">{{ $todayStatus }}</h3>
                    <p class="ui-section-subtitle mt-2">
                        Check In: {{ $todayRecord?->check_in_at?->format('h:i A') ?? 'Not marked' }}
                        •
                        Check Out: {{ $todayRecord?->check_out_at?->format('h:i A') ?? 'Not marked' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('modules.attendance.check-in') }}">
                    @csrf
                    <button
                        type="submit"
                        class="ui-btn ui-btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $canCheckIn ? '' : 'disabled' }}
                    >
                        Check In
                    </button>
                </form>
                <form method="POST" action="{{ route('modules.attendance.check-out') }}">
                    @csrf
                    <button
                        type="submit"
                        class="ui-btn ui-btn-ghost disabled:opacity-60 disabled:cursor-not-allowed"
                        {{ $canCheckOut ? '' : 'disabled' }}
                    >
                        Check Out
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Records This Month</p>
                    <p class="ui-kpi-value">{{ $stats['monthRecords'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Present Days</p>
                    <p class="ui-kpi-value">{{ $stats['presentCount'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Absent Days</p>
                    <p class="ui-kpi-value">{{ $stats['absentCount'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-pink">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Avg Daily Hours</p>
                    <p class="ui-kpi-value">{{ number_format($stats['averageDailyHours'], 1) }}h</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Attendance History</h3>
                <p class="ui-section-subtitle">View and filter your attendance logs.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.attendance.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <input
                type="month"
                name="month"
                value="{{ $filters['month'] }}"
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
            <div class="flex items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                <a href="{{ route('modules.attendance.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>
        </form>

        <div class="ui-table-wrap">
            <table class="ui-table" style="min-width: 780px;">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $statusStyles = match ($record->status) {
                            'absent' => 'color:#b91c1c;background:rgb(239 68 68 / 0.18);',
                            'half_day' => 'color:#b45309;background:rgb(245 158 11 / 0.18);',
                            'on_leave' => 'color:#1d4ed8;background:rgb(59 130 246 / 0.18);',
                            'remote' => 'color:#0369a1;background:rgb(14 165 233 / 0.18);',
                            default => 'color:#15803d;background:rgb(34 197 94 / 0.16);',
                        };
                    @endphp
                    <tr>
                        <td>{{ $record->attendance_date?->format('M d, Y') }}</td>
                        <td>
                            <span class="ui-status-chip" style="{{ $statusStyles }}">
                                {{ str($record->status)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                        <td>{{ $record->check_in_at?->format('h:i A') ?? 'N/A' }}</td>
                        <td>{{ $record->check_out_at?->format('h:i A') ?? 'N/A' }}</td>
                        <td>{{ $record->work_minutes !== null ? number_format($record->work_minutes / 60, 2).'h' : 'N/A' }}</td>
                        <td>{{ $record->notes ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ui-empty">No attendance records for the selected month/filter.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $records->links() }}
        </div>
    </section>
@endsection
