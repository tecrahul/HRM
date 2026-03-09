@extends('layouts.dashboard-modern')

@section('title', 'Admin Dashboard')
@section('page_heading', 'Admin Command Center')

@section('content')
@php
    $dashUser        = auth()->user();
    $canManageUsers  = $dashUser?->hasAnyRole([
        \App\Enums\UserRole::SUPER_ADMIN->value,
        \App\Enums\UserRole::ADMIN->value,
        \App\Enums\UserRole::HR->value,
    ]) ?? false;
    $canSeePayroll   = $dashUser?->hasAnyRole([
        \App\Enums\UserRole::SUPER_ADMIN->value,
        \App\Enums\UserRole::ADMIN->value,
        \App\Enums\UserRole::HR->value,
        \App\Enums\UserRole::FINANCE->value,
    ]) ?? false;
@endphp
    <section class="ui-hero">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-5">
            <div>
                <p class="ui-kpi-label">Platform Overview</p>
                @include('dashboard.partials.greeting-header', ['functionalTitle' => 'Admin Command Center'])
                <p class="ui-section-subtitle">Monitor users, employees, attendance, leave, payroll, departments, and branches from one dashboard.</p>
            </div>
            <div class="flex flex-col items-stretch gap-2 w-full md:w-56 ui-btn-stack">
                @if ($canManageUsers)
                <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-primary w-full justify-center">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Manage Users
                </a>
                @endif
                <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-ghost w-full justify-center">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Employee Directory
                </a>
                @if ($canSeePayroll)
                <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost w-full justify-center">
                    <x-heroicon-o-banknotes class="h-4 w-4" />
                    Run Payroll
                </a>
                @endif
            </div>
        </div>
    </section>

    <section class="ui-section">
        <div
            id="admin-dashboard-summary-cards-root"
            data-summary-endpoint="{{ route('api.dashboard.admin.summary') }}"
        >
            <div class="rounded-2xl border p-4 text-sm font-semibold" style="background: var(--hr-surface-strong); border-color: var(--hr-line); color: var(--hr-text-muted);">
                Loading dashboard summary...
            </div>
        </div>
    </section>

    <section>
        <div
            id="admin-dashboard-work-hours-root"
            data-avg-endpoint="{{ route('api.dashboard.admin.work-hours.avg') }}"
            data-monthly-endpoint="{{ route('api.dashboard.admin.work-hours.monthly') }}"
        >
            <div class="rounded-xl border p-4 text-sm font-semibold" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                Loading work hours widgets...
            </div>
        </div>
    </section>

    <section>
        <div
            id="admin-dashboard-attendance-overview-root"
            data-endpoint="{{ route('api.dashboard.admin.attendance-overview') }}"
            data-absent-url="{{ route('modules.attendance.overview', ['status' => 'absent', 'attendance_date' => now()->toDateString()]) }}"
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
            @if ($canManageUsers)
            <a href="{{ route('admin.users.create') }}" class="ui-btn ui-btn-primary">
                <x-heroicon-o-plus class="h-4 w-4" />
                Add Employee
            </a>
            @endif
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
                        <td><a href="{{ route('admin.users.edit', $employee) }}" class="ui-btn ui-btn-ghost">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            Open
                        </a></td>
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
