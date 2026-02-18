@extends('layouts.dashboard-modern')

@section('title', 'Attendance')
@section('page_heading', 'Attendance Management')

@push('head')
    <style>
        .att-hero {
            border: 1px solid rgb(125 211 252 / 0.35);
            background: linear-gradient(140deg, rgb(219 234 254 / 0.88), rgb(224 231 255 / 0.9), rgb(245 208 254 / 0.8));
            box-shadow: 0 24px 44px -34px rgb(30 41 59 / 0.55);
        }

        html.dark .att-hero {
            border-color: rgb(56 87 135 / 0.46);
            background: linear-gradient(145deg, rgb(10 17 32 / 0.95), rgb(17 34 64 / 0.92), rgb(48 25 84 / 0.76));
            box-shadow: 0 24px 44px -34px rgb(2 8 23 / 0.92);
        }

        .att-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.85rem;
        }

        @media (min-width: 768px) {
            .att-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .att-kpi-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .att-kpi-card {
            border: 1px solid rgb(148 163 184 / 0.32);
            background: var(--hr-surface);
            border-radius: 1rem;
            padding: 0.95rem;
            box-shadow: 0 18px 36px -28px rgb(2 8 23 / 0.8);
        }

        .att-kpi-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--hr-text-muted);
        }

        .att-kpi-value {
            margin-top: 0.45rem;
            font-size: 1.7rem;
            line-height: 1.1;
            font-weight: 800;
        }

        .att-kpi-meta {
            margin-top: 0.35rem;
            font-size: 0.74rem;
            color: var(--hr-text-muted);
        }

        .att-kpi-trend {
            margin-top: 0.45rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .att-kpi-trend.is-up {
            color: rgb(22 163 74);
        }

        .att-kpi-trend.is-down {
            color: rgb(220 38 38);
        }

        .att-kpi-trend.is-neutral {
            color: var(--hr-text-muted);
        }

        .att-kpi-progress {
            margin-top: 0.6rem;
            height: 0.3rem;
            width: 100%;
            overflow: hidden;
            border-radius: 999px;
            background: rgb(148 163 184 / 0.28);
        }

        .att-kpi-progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .att-hero-btn {
            border: 1px solid rgb(192 132 252 / 0.72);
            color: #ecfeff;
            box-shadow: 0 20px 38px -24px rgb(124 58 237 / 0.9);
            background: linear-gradient(120deg, #7c3aed, #ec4899);
        }
    </style>
@endpush

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

    @php
        $totalEmployees = (int) ($stats['totalEmployees'] ?? 0);
        $markedToday = (int) ($stats['markedToday'] ?? 0);
        $presentToday = (int) ($stats['presentToday'] ?? 0);
        $pendingToday = (int) ($stats['pendingToday'] ?? 0);
        $recordsThisMonth = (int) ($stats['recordsThisMonth'] ?? 0);
        $headcountDelta = (int) ($statTrends['headcountDelta'] ?? 0);
        $markedDelta = (int) ($statTrends['markedDelta'] ?? 0);
        $presentDelta = (int) ($statTrends['presentDelta'] ?? 0);
        $pendingDelta = (int) ($statTrends['pendingDelta'] ?? 0);
        $coverageToday = (float) ($statTrends['coverageToday'] ?? ($totalEmployees > 0 ? round(($markedToday / $totalEmployees) * 100, 1) : 0.0));
        $presentShareToday = (float) ($statTrends['presentShareToday'] ?? ($markedToday > 0 ? round(($presentToday / $markedToday) * 100, 1) : 0.0));
        $pendingShareToday = $totalEmployees > 0 ? round(($pendingToday / $totalEmployees) * 100, 1) : 0.0;
        $markedYesterday = (int) ($statTrends['markedYesterday'] ?? 0);
        $presentYesterday = (int) ($statTrends['presentYesterday'] ?? 0);
        $pendingYesterday = (int) ($statTrends['pendingYesterday'] ?? 0);
        $headcountTrendLabel = $headcountDelta === 0
            ? 'No change vs last month'
            : (($headcountDelta > 0 ? '+' : '').$headcountDelta.' vs last month');
        $markedTrendLabel = $markedDelta === 0
            ? 'No change vs yesterday'
            : (($markedDelta > 0 ? '+' : '').$markedDelta.' vs yesterday');
        $presentTrendLabel = $presentDelta === 0
            ? 'No change vs yesterday'
            : (($presentDelta > 0 ? '+' : '').$presentDelta.' vs yesterday');
        $pendingTrendLabel = $pendingDelta === 0
            ? 'No change vs yesterday'
            : (($pendingDelta > 0 ? '+' : '').$pendingDelta.' vs yesterday');
        $headcountTrendClass = $headcountDelta > 0 ? 'is-up' : ($headcountDelta < 0 ? 'is-down' : 'is-neutral');
        $markedTrendClass = $markedDelta > 0 ? 'is-up' : ($markedDelta < 0 ? 'is-down' : 'is-neutral');
        $presentTrendClass = $presentDelta > 0 ? 'is-up' : ($presentDelta < 0 ? 'is-down' : 'is-neutral');
        $pendingTrendClass = $pendingDelta < 0 ? 'is-up' : ($pendingDelta > 0 ? 'is-down' : 'is-neutral');
        $headcountProgress = $totalEmployees > 0 ? round((max(0, $headcountDelta) / $totalEmployees) * 100, 1) : 0.0;
    @endphp

    <section class="att-hero rounded-3xl p-4 md:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Attendance Overview</p>
                <h3 class="mt-1 text-xl md:text-2xl font-extrabold">Daily Attendance Pulse</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Monitor coverage, active presence, and pending check-ins in real time.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-2.5">
                <a href="#mark-attendance-form" class="att-hero-btn inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-semibold">
                    Mark Attendance
                </a>
                <a href="#attendance-directory" class="ui-btn ui-btn-ghost">View Directory</a>
            </div>
        </div>

        <div class="att-kpi-grid mt-5">
            <article class="att-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="att-kpi-label">Total Employees</p>
                        <p class="att-kpi-value">{{ number_format($totalEmployees) }}</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-violet">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                    </span>
                </div>
                <p class="att-kpi-meta">{{ number_format($recordsThisMonth) }} records this month</p>
                <p class="att-kpi-trend {{ $headcountTrendClass }}">{{ $headcountTrendLabel }}</p>
                <div class="att-kpi-progress" aria-hidden="true">
                    <span style="width: {{ $headcountProgress > 0 ? max(8, $headcountProgress) : 0 }}%; background: linear-gradient(120deg, #7c3aed, #ec4899);"></span>
                </div>
            </article>

            <article class="att-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="att-kpi-label">Marked Today</p>
                        <p class="att-kpi-value">{{ number_format($markedToday) }}</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-sky">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                    </span>
                </div>
                <p class="att-kpi-meta">Coverage: {{ number_format($coverageToday, 1) }}%</p>
                <p class="att-kpi-trend {{ $markedTrendClass }}">{{ $markedTrendLabel }} (yesterday {{ $markedYesterday }})</p>
                <div class="att-kpi-progress" aria-hidden="true">
                    <span style="width: {{ $coverageToday > 0 ? max(8, $coverageToday) : 0 }}%; background: linear-gradient(120deg, #0284c7, #38bdf8);"></span>
                </div>
            </article>

            <article class="att-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="att-kpi-label">Present Today</p>
                        <p class="att-kpi-value">{{ number_format($presentToday) }}</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-green">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                    </span>
                </div>
                <p class="att-kpi-meta">Present ratio: {{ number_format($presentShareToday, 1) }}% of marked</p>
                <p class="att-kpi-trend {{ $presentTrendClass }}">{{ $presentTrendLabel }} (yesterday {{ $presentYesterday }})</p>
                <div class="att-kpi-progress" aria-hidden="true">
                    <span style="width: {{ $presentShareToday > 0 ? max(8, $presentShareToday) : 0 }}%; background: linear-gradient(120deg, #16a34a, #4ade80);"></span>
                </div>
            </article>

            <article class="att-kpi-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="att-kpi-label">Pending Today</p>
                        <p class="att-kpi-value">{{ number_format($pendingToday) }}</p>
                    </div>
                    <span class="ui-icon-chip ui-icon-amber">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
                    </span>
                </div>
                <p class="att-kpi-meta">Coverage gap: {{ number_format($pendingShareToday, 1) }}%</p>
                <p class="att-kpi-trend {{ $pendingTrendClass }}">{{ $pendingTrendLabel }} (yesterday {{ $pendingYesterday }})</p>
                <div class="att-kpi-progress" aria-hidden="true">
                    <span style="width: {{ $pendingShareToday > 0 ? max(8, $pendingShareToday) : 0 }}%; background: linear-gradient(120deg, #d97706, #fbbf24);"></span>
                </div>
            </article>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article id="mark-attendance-form" class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Mark Attendance</h3>
                    <p class="ui-section-subtitle">Create or update attendance records for any employee.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.attendance.store') }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="attendance_user_id" class="ui-kpi-label block mb-2">Employee</label>
                    <div
                        data-employee-autocomplete-root
                        data-api-url="{{ route('api.employees.search') }}"
                        data-name="user_id"
                        data-input-id="attendance_user_id"
                        data-required="true"
                        data-selected='@json($selectedCreateEmployee)'
                    ></div>
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

    <section id="attendance-directory" class="ui-section">
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
                placeholder="Search notes, department, or branch..."
                class="ui-input md:col-span-2"
            >

            <div>
                <div
                    data-employee-autocomplete-root
                    data-api-url="{{ route('api.employees.search') }}"
                    data-name="employee_id"
                    data-input-id="attendance_filter_employee_id"
                    data-selected='@json($selectedFilterEmployee)'
                ></div>
            </div>

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
