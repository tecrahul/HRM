@extends('layouts.dashboard-modern')

@section('title', 'Attendance')
@section('page_heading', 'Attendance Management')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please review attendance form errors and try again.</div>
    @endif

    <section class="ui-kpi-grid is-5">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Total Employees</p>
                    <p class="ui-kpi-value">{{ $stats['totalEmployees'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Marked Today</p>
                    <p class="ui-kpi-value">{{ $stats['markedToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Present Today</p>
                    <p class="ui-kpi-value">{{ $stats['presentToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Absent Today</p>
                    <p class="ui-kpi-value">{{ $stats['absentToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-pink">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Pending Today</p>
                    <p class="ui-kpi-value">{{ $stats['pendingToday'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-amber">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Mark Attendance</h3>
                    <p class="ui-section-subtitle">Create or update attendance records for any employee.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.attendance.store') }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="user_id" class="ui-kpi-label block mb-2">Employee</label>
                    <select id="user_id" name="user_id" class="ui-select">
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            @php
                                $profile = $employee->profile;
                            @endphp
                            <option value="{{ $employee->id }}" {{ (string) old('user_id') === (string) $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }} ({{ $profile?->department ?? 'No Department' }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="attendance_date" class="ui-kpi-label block mb-2">Attendance Date</label>
                    <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', now()->toDateString()) }}" class="ui-input">
                    @error('attendance_date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status" class="ui-kpi-label block mb-2">Status</label>
                    <select id="status" name="status" class="ui-select">
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" {{ old('status', 'present') === $statusOption ? 'selected' : '' }}>
                                {{ str($statusOption)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="check_in_time" class="ui-kpi-label block mb-2">Check In</label>
                        <input id="check_in_time" name="check_in_time" type="time" value="{{ old('check_in_time') }}" class="ui-input">
                        @error('check_in_time')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="check_out_time" class="ui-kpi-label block mb-2">Check Out</label>
                        <input id="check_out_time" name="check_out_time" type="time" value="{{ old('check_out_time') }}" class="ui-input">
                        @error('check_out_time')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="ui-kpi-label block mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="ui-textarea resize-y">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary">Save Attendance</button>
                </div>
            </form>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Status Breakdown</h3>
                    <p class="ui-section-subtitle">Current month attendance by status.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($statusBreakdown as $item)
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold">{{ str($item->status)->replace('_', ' ')->title() }}</p>
                            <span class="ui-status-chip" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                                {{ $item->record_count }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">No attendance records available yet.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Attendance Directory</h3>
                <p class="ui-section-subtitle">Filter and audit attendance records.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.attendance.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input
                type="text"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="Search by employee, email, department..."
                class="ui-input md:col-span-2"
            >

            <select name="employee_id" class="ui-select">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ (string) $filters['employee_id'] === (string) $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>

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

            <div class="flex items-center gap-2">
                <select name="status" class="ui-select">
                    <option value="">All Status</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>
                            {{ str($statusOption)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                <a href="{{ route('modules.attendance.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
            </div>

            <input type="date" name="attendance_date" value="{{ $filters['attendance_date'] }}" class="ui-input">
        </form>

        <div class="ui-table-wrap">
            <table class="ui-table" style="min-width: 980px;">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Marked By</th>
                    <th class="text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $profile = $record->user?->profile;
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
                            <p class="font-semibold">{{ $record->user?->name }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $record->user?->email }}</p>
                        </td>
                        <td>{{ $profile?->department ?? 'Unassigned' }}</td>
                        <td>{{ $profile?->branch ?? 'Unassigned' }}</td>
                        <td>
                            <span class="ui-status-chip" style="{{ $statusStyles }}">
                                {{ str($record->status)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                        <td>{{ $record->check_in_at?->format('h:i A') ?? 'N/A' }}</td>
                        <td>{{ $record->check_out_at?->format('h:i A') ?? 'N/A' }}</td>
                        <td>{{ $record->work_minutes !== null ? number_format($record->work_minutes / 60, 2).'h' : 'N/A' }}</td>
                        <td>{{ $record->markedBy?->name ?? 'System' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('modules.attendance.edit', $record) }}" class="ui-btn ui-btn-ghost">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="ui-empty">No attendance records found for selected filters.</td>
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
