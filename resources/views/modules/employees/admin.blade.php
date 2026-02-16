@extends('layouts.dashboard-modern')

@section('title', 'Employees')
@section('page_heading', 'Employee Directory')

@push('head')
    <style>
        .emp-admin-theme {
            --emp-hero-bg: linear-gradient(140deg, rgb(237 233 254 / 0.95), rgb(252 231 243 / 0.95));
            --emp-hero-border: rgb(139 92 246 / 0.28);
            --emp-panel-bg: var(--hr-surface);
            --emp-panel-border: var(--hr-line);
            --emp-panel-shadow: var(--hr-shadow-soft);
            --emp-text-main: var(--hr-text-main);
            --emp-text-muted: var(--hr-text-muted);
            --emp-tab-text: var(--hr-text-muted);
            --emp-tab-active: #2563eb;
            --emp-control-bg: var(--hr-surface-strong);
            --emp-control-border: rgb(148 163 184 / 0.42);
            --emp-control-text: var(--hr-text-main);
            --emp-control-placeholder: rgb(100 116 139 / 0.85);
            --emp-card-bg: var(--hr-surface-strong);
            --emp-card-border: rgb(148 163 184 / 0.32);
            --emp-card-selected-border: rgb(34 197 94 / 0.88);
            --emp-card-selected-bg: rgb(34 197 94 / 0.08);
            --emp-menu-bg: var(--hr-surface-strong);
            --emp-table-row-hover: rgb(37 99 235 / 0.08);
            --emp-table-row-selected: rgb(34 197 94 / 0.12);
            --emp-job-color: #2563eb;
        }

        html.dark .emp-admin-theme {
            --emp-hero-bg: linear-gradient(145deg, rgb(10 17 32 / 0.96), rgb(17 34 64 / 0.94));
            --emp-hero-border: rgb(56 87 135 / 0.45);
            --emp-panel-bg: rgb(10 18 34 / 0.92);
            --emp-panel-border: rgb(53 80 124 / 0.42);
            --emp-panel-shadow: 0 24px 48px -34px rgb(2 8 23 / 0.9);
            --emp-text-main: rgb(226 232 240 / 0.98);
            --emp-text-muted: rgb(152 168 197 / 0.92);
            --emp-tab-text: rgb(163 184 213 / 0.9);
            --emp-tab-active: rgb(56 189 248 / 0.95);
            --emp-control-bg: rgb(10 20 38 / 0.85);
            --emp-control-border: rgb(63 92 135 / 0.58);
            --emp-control-text: rgb(224 236 255 / 0.98);
            --emp-control-placeholder: rgb(148 173 207 / 0.82);
            --emp-card-bg: rgb(12 24 44 / 0.9);
            --emp-card-border: rgb(64 94 136 / 0.45);
            --emp-card-selected-border: rgb(74 222 128 / 0.9);
            --emp-card-selected-bg: rgb(13 30 48 / 0.95);
            --emp-menu-bg: rgb(11 22 40 / 0.98);
            --emp-table-row-hover: rgb(59 130 246 / 0.16);
            --emp-table-row-selected: rgb(34 197 94 / 0.17);
            --emp-job-color: rgb(125 211 252 / 0.95);
        }

        .emp-admin-hero {
            background: var(--emp-hero-bg);
            border: 1px solid var(--emp-hero-border);
            box-shadow: var(--emp-panel-shadow);
        }

        .emp-toolbar,
        .emp-directory,
        .emp-breakdown {
            background: var(--emp-panel-bg);
            border: 1px solid var(--emp-panel-border);
            box-shadow: var(--emp-panel-shadow);
        }

        .emp-main-text {
            color: var(--emp-text-main);
        }

        .emp-muted {
            color: var(--emp-text-muted);
        }

        .emp-tab-link {
            color: var(--emp-tab-text);
            border-bottom: 2px solid transparent;
            transition: color 150ms ease, border-color 150ms ease;
        }

        .emp-tab-link.is-active {
            color: var(--emp-text-main);
            border-color: var(--emp-tab-active);
        }

        .emp-view-toggle {
            border: 1px solid var(--emp-control-border);
            background: var(--emp-control-bg);
            color: var(--emp-text-muted);
        }

        .emp-view-toggle.is-active {
            color: #ecfeff;
            background: linear-gradient(145deg, rgb(37 99 235 / 0.75), rgb(56 189 248 / 0.52));
            border-color: rgb(96 165 250 / 0.88);
        }

        .emp-search,
        .emp-filter-select {
            border: 1px solid var(--emp-control-border);
            background: var(--emp-control-bg);
            color: var(--emp-control-text);
        }

        .emp-search::placeholder {
            color: var(--emp-control-placeholder);
        }

        .emp-filter-select option {
            background: var(--emp-control-bg);
            color: var(--emp-control-text);
        }

        .emp-filter-apply {
            border: 1px solid rgb(96 165 250 / 0.72);
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #ecfeff;
            box-shadow: 0 18px 36px -24px rgb(37 99 235 / 0.9);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .emp-filter-apply:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 42px -24px rgb(56 189 248 / 0.9);
        }

        .emp-filter-apply.is-attention {
            box-shadow: 0 0 0 2px rgb(125 211 252 / 0.65), 0 24px 42px -24px rgb(56 189 248 / 0.95);
        }

        .emp-card-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .emp-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .emp-card-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .emp-card {
            background: var(--emp-card-bg);
            border: 1px solid var(--emp-card-border);
            box-shadow: 0 18px 38px -28px rgb(2 8 23 / 0.96);
            border-radius: 1rem;
            transition: transform 160ms ease, border-color 160ms ease, background-color 160ms ease;
        }

        .emp-card:hover {
            transform: translateY(-2px);
            border-color: rgb(96 165 250 / 0.75);
        }

        .emp-card.is-selected {
            border-color: var(--emp-card-selected-border);
            background: var(--emp-card-selected-bg);
            box-shadow: 0 0 0 1px rgb(74 222 128 / 0.24), 0 22px 44px -28px rgb(16 185 129 / 0.62);
        }

        .emp-status {
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.66rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .emp-status.is-active {
            color: rgb(110 231 183);
            border-color: rgb(52 211 153 / 0.58);
            background: rgb(5 150 105 / 0.2);
        }

        .emp-status.is-on-leave,
        .emp-status.is-suspended {
            color: rgb(252 165 165);
            border-color: rgb(248 113 113 / 0.58);
            background: rgb(239 68 68 / 0.2);
        }

        .emp-status.is-inactive {
            color: rgb(253 230 138);
            border-color: rgb(250 204 21 / 0.58);
            background: rgb(202 138 4 / 0.24);
        }

        .emp-menu-trigger {
            border: 1px solid var(--emp-control-border);
            background: var(--emp-control-bg);
            color: var(--emp-text-muted);
        }

        .emp-menu-panel {
            background: var(--emp-menu-bg);
            border: 1px solid var(--emp-control-border);
            box-shadow: 0 24px 38px -30px rgb(2 8 23 / 0.95);
        }

        .emp-info-tile {
            border: 1px solid var(--emp-card-border);
            background: var(--emp-control-bg);
        }

        .emp-contact-row {
            border-top: 1px solid var(--emp-card-border);
        }

        .emp-breakdown-bar {
            background: linear-gradient(120deg, rgb(37 99 235 / 0.88), rgb(56 189 248 / 0.88));
        }

        .emp-list-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .emp-list-table th,
        .emp-list-table td {
            border-bottom: 1px solid var(--emp-panel-border);
            padding: 0.82rem 0.75rem;
            text-align: left;
            vertical-align: middle;
        }

        .emp-list-table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: var(--emp-text-muted);
        }

        .emp-list-table tbody tr {
            transition: background-color 130ms ease;
        }

        .emp-list-table tbody tr:hover {
            background: var(--emp-table-row-hover);
        }

        .emp-list-table tbody tr.is-selected {
            background: var(--emp-table-row-selected);
        }
    </style>
@endpush

@section('content')
    @php
        $dashboardRoute = auth()->user()?->dashboardRouteName() ?? 'dashboard';
        $viewMode = in_array((string) request()->query('view', 'grid'), ['grid', 'list'], true)
            ? (string) request()->query('view', 'grid')
            : 'grid';
        $baseQuery = request()->except(['page', 'view']);
        $statusFilter = (string) ($filters['status'] ?? '');
        $onLeaveLookup = array_flip(array_map('intval', $onLeaveUserIds ?? []));
        $employeeIdsOnPage = $employees->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        $preferredSelectedId = (int) ($selectedEmployeeId ?? 0);
        $resolvedSelectedId = in_array($preferredSelectedId, $employeeIdsOnPage, true)
            ? $preferredSelectedId
            : ($employeeIdsOnPage[0] ?? null);
        $maxDepartmentCount = (int) ($departmentBreakdown->max('employee_count') ?? 0);
    @endphp

    <div class="emp-admin-theme space-y-6">
        @if (session('status'))
            <section class="rounded-2xl px-4 py-3 border border-emerald-400/40 bg-emerald-500/10 text-emerald-200 text-sm font-semibold">
                {{ session('status') }}
            </section>
        @endif

        @if (session('error'))
            <section class="rounded-2xl px-4 py-3 border border-red-400/40 bg-red-500/10 text-red-200 text-sm font-semibold">
                {{ session('error') }}
            </section>
        @endif

        <section class="emp-admin-hero rounded-3xl px-5 py-5 md:px-6 md:py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold tracking-[0.02em] emp-main-text">{{ number_format((int) ($stats['total'] ?? 0)) }} Employee</p>
                    <h3 class="mt-1 text-2xl md:text-3xl font-extrabold tracking-tight emp-main-text">Employees Dashboard</h3>
                    <p class="mt-2 text-sm emp-muted inline-flex items-center gap-2">
                        <a href="{{ route($dashboardRoute) }}" class="hover:text-sky-500 transition-colors">Dashboard</a>
                        <span>/</span>
                        <span class="emp-main-text">Employee</span>
                    </p>
                </div>
                @if ($canManageUsers)
                    <a
                        href="{{ route('admin.users.create', ['role' => \App\Enums\UserRole::EMPLOYEE->value]) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/35"
                        style="background: linear-gradient(135deg, #1d4ed8, #38bdf8);"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                        + Add Employee
                    </a>
                @endif
            </div>
        </section>

        <section class="emp-toolbar rounded-3xl p-4 md:p-5">
            <form id="employeeFiltersForm" method="GET" action="{{ route('modules.employees.index') }}" class="space-y-4">
                <input type="hidden" name="view" value="{{ $viewMode }}">

                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-5 text-sm font-semibold">
                        <a href="{{ route('modules.employees.index', array_merge($baseQuery, ['view' => $viewMode])) }}" class="emp-tab-link {{ request()->routeIs('modules.employees.index') ? 'is-active' : '' }} pb-1">
                            Employee
                        </a>
                        <a href="{{ route('modules.leave.index') }}" class="emp-tab-link pb-1">
                            Leave Request
                        </a>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center rounded-xl p-1" style="border: 1px solid var(--emp-control-border); background: var(--emp-control-bg);">
                            <a href="{{ route('modules.employees.index', array_merge($baseQuery, ['view' => 'grid'])) }}" class="emp-view-toggle {{ $viewMode === 'grid' ? 'is-active' : '' }} inline-flex h-9 w-9 items-center justify-center rounded-lg" aria-label="Grid view">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                </svg>
                            </a>
                            <a href="{{ route('modules.employees.index', array_merge($baseQuery, ['view' => 'list'])) }}" class="emp-view-toggle {{ $viewMode === 'list' ? 'is-active' : '' }} inline-flex h-9 w-9 items-center justify-center rounded-lg" aria-label="List view">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                            </a>
                        </div>

                        <label class="relative w-full sm:w-72">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center" style="color: var(--emp-control-placeholder);">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="M21 21l-4.35-4.35"></path>
                                </svg>
                            </span>
                            <input
                                id="employeeSearchInput"
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="Search Employee"
                                class="emp-search w-full rounded-xl py-2.5 pl-10 pr-3 text-sm outline-none"
                            >
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <select name="department" data-auto-submit="true" class="emp-filter-select rounded-xl px-3 py-2.5 text-sm outline-none">
                        <option value="">All Departments</option>
                        @foreach ($departmentOptions as $departmentOption)
                            <option value="{{ $departmentOption }}" {{ ($filters['department'] ?? '') === $departmentOption ? 'selected' : '' }}>{{ $departmentOption }}</option>
                        @endforeach
                    </select>

                    <select name="branch" data-auto-submit="true" class="emp-filter-select rounded-xl px-3 py-2.5 text-sm outline-none">
                        <option value="">All Branches</option>
                        @foreach ($branchOptions as $branchOption)
                            <option value="{{ $branchOption }}" {{ ($filters['branch'] ?? '') === $branchOption ? 'selected' : '' }}>{{ $branchOption }}</option>
                        @endforeach
                    </select>

                    <select name="status" data-auto-submit="true" class="emp-filter-select rounded-xl px-3 py-2.5 text-sm outline-none">
                        <option value="">All Status</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" {{ $statusFilter === $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>

                    <button id="employeeFilterSubmit" type="submit" class="emp-filter-apply rounded-xl px-3 py-2.5 text-sm font-bold">
                        Apply Filters
                    </button>
                </div>
            </form>
        </section>

        <section class="emp-directory rounded-3xl p-4 md:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-lg font-extrabold emp-main-text">Employee Directory</h4>
                    <p class="text-sm emp-muted mt-1">Showing {{ $employees->count() }} of {{ number_format((int) ($stats['total'] ?? 0)) }} employee records.</p>
                </div>
            </div>

            @if ($viewMode === 'list')
                <div class="mt-5 overflow-x-auto">
                    <table class="emp-list-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Status</th>
                                <th>Department</th>
                                <th>Hired Date</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                @php
                                    $profile = $employee->profile;
                                    $profileStatus = strtolower((string) ($profile?->status ?? 'active'));
                                    $isOnLeave = isset($onLeaveLookup[(int) $employee->id]);

                                    if ($isOnLeave) {
                                        $statusLabel = 'On Leave';
                                        $statusClass = 'is-on-leave';
                                    } elseif ($profileStatus === 'inactive') {
                                        $statusLabel = 'Inactive';
                                        $statusClass = 'is-inactive';
                                    } elseif ($profileStatus === 'suspended') {
                                        $statusLabel = 'Suspended';
                                        $statusClass = 'is-suspended';
                                    } else {
                                        $statusLabel = 'Active';
                                        $statusClass = 'is-active';
                                    }

                                    $cardSelectUrl = route('modules.employees.index', array_merge(request()->except('selected'), ['selected' => $employee->id]));
                                    $isSelected = $resolvedSelectedId !== null && (int) $employee->id === (int) $resolvedSelectedId;
                                @endphp

                                <tr class="{{ $isSelected ? 'is-selected' : '' }}">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="{{ asset('images/user-avatar.svg') }}" alt="{{ $employee->name }} profile" class="h-10 w-10 rounded-full border object-cover" style="border-color: var(--emp-card-border);">
                                            <div>
                                                <p class="text-sm font-bold emp-main-text">{{ $employee->name }}</p>
                                                <p class="text-xs font-semibold" style="color: var(--emp-job-color);">{{ $profile?->job_title ?? 'Employee' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="emp-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>
                                        <p class="text-sm font-semibold emp-main-text">{{ $profile?->department ?? 'Unassigned' }}</p>
                                        <p class="text-xs emp-muted">{{ $profile?->branch ?? 'No branch' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm emp-main-text">{{ $profile?->joined_on?->format('M d, Y') ?? 'Not set' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm emp-main-text">{{ $employee->email }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm emp-main-text">{{ $profile?->phone ?? 'No phone number' }}</p>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ $cardSelectUrl }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--emp-control-border); color: var(--emp-text-main);">Select</a>
                                            @if ($canManageUsers)
                                                <a href="{{ route('admin.users.edit', $employee) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--emp-control-border); color: var(--emp-text-main);">Edit</a>
                                                <form method="POST" action="{{ route('admin.users.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-300" style="border-color: rgb(248 113 113 / 0.45);">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <p class="py-8 text-sm emp-muted">No employee records found for current filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div id="employeeCardGrid" class="emp-card-grid mt-5">
                    @forelse ($employees as $employee)
                        @php
                            $profile = $employee->profile;
                            $profileStatus = strtolower((string) ($profile?->status ?? 'active'));
                            $isOnLeave = isset($onLeaveLookup[(int) $employee->id]);

                            if ($isOnLeave) {
                                $statusLabel = 'On Leave';
                                $statusClass = 'is-on-leave';
                            } elseif ($profileStatus === 'inactive') {
                                $statusLabel = 'Inactive';
                                $statusClass = 'is-inactive';
                            } elseif ($profileStatus === 'suspended') {
                                $statusLabel = 'Suspended';
                                $statusClass = 'is-suspended';
                            } else {
                                $statusLabel = 'Active';
                                $statusClass = 'is-active';
                            }

                            $cardSelectUrl = route('modules.employees.index', array_merge(request()->except('selected'), ['selected' => $employee->id]));
                            $isSelected = $resolvedSelectedId !== null && (int) $employee->id === (int) $resolvedSelectedId;
                        @endphp

                        <article class="emp-card {{ $isSelected ? 'is-selected' : '' }} p-4 md:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="emp-status {{ $statusClass }}">{{ $statusLabel }}</span>

                                <details class="relative">
                                    <summary class="emp-menu-trigger list-none h-8 w-8 rounded-lg inline-flex items-center justify-center cursor-pointer">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="5" r="1"></circle>
                                            <circle cx="12" cy="12" r="1"></circle>
                                            <circle cx="12" cy="19" r="1"></circle>
                                        </svg>
                                    </summary>
                                    <div class="emp-menu-panel absolute right-0 z-20 mt-2 w-44 rounded-xl p-2 text-sm">
                                        <a href="{{ $cardSelectUrl }}" class="block rounded-lg px-2.5 py-2 hover:bg-blue-500/20 emp-main-text">Select Card</a>
                                        @if ($canManageUsers)
                                            <a href="{{ route('admin.users.edit', $employee) }}" class="block rounded-lg px-2.5 py-2 hover:bg-blue-500/20 emp-main-text">Edit Employee</a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full text-left rounded-lg px-2.5 py-2 text-red-300 hover:bg-red-500/20">Delete Employee</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <img src="{{ asset('images/user-avatar.svg') }}" alt="{{ $employee->name }} profile" class="h-14 w-14 rounded-full border object-cover" style="border-color: var(--emp-card-border);">
                                <div>
                                    <h5 class="text-base font-bold emp-main-text">{{ $employee->name }}</h5>
                                    <p class="text-sm font-semibold" style="color: var(--emp-job-color);">{{ $profile?->job_title ?? 'Employee' }}</p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2.5 text-sm">
                                <div class="emp-info-tile rounded-xl p-3">
                                    <p class="text-[11px] uppercase tracking-[0.08em] emp-muted">Department</p>
                                    <p class="mt-1 font-semibold emp-main-text">{{ $profile?->department ?? 'Unassigned' }}</p>
                                </div>
                                <div class="emp-info-tile rounded-xl p-3">
                                    <p class="text-[11px] uppercase tracking-[0.08em] emp-muted">Hired Date</p>
                                    <p class="mt-1 font-semibold emp-main-text">{{ $profile?->joined_on?->format('M d, Y') ?? 'Not set' }}</p>
                                </div>
                            </div>

                            <div class="emp-contact-row mt-4 space-y-2 pt-3 text-sm">
                                <p class="inline-flex w-full items-center gap-2 truncate emp-main-text">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/15 text-sky-400">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16v16H4z"></path>
                                            <path d="m22 6-10 7L2 6"></path>
                                        </svg>
                                    </span>
                                    <span class="truncate">{{ $employee->email }}</span>
                                </p>
                                <p class="inline-flex w-full items-center gap-2 truncate emp-main-text">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/15 text-sky-400">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92V20a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07A19.5 19.5 0 0 1 5.07 12.8 19.86 19.86 0 0 1 2 4.18 2 2 0 0 1 4 2h3.09a2 2 0 0 1 2 1.72c.12.9.34 1.78.66 2.62a2 2 0 0 1-.45 2.11L8.1 9.91a16 16 0 0 0 6 6l1.46-1.2a2 2 0 0 1 2.11-.45c.84.32 1.72.54 2.62.66A2 2 0 0 1 22 16.92z"></path>
                                        </svg>
                                    </span>
                                    <span class="truncate">{{ $profile?->phone ?? 'No phone number' }}</span>
                                </p>
                            </div>
                        </article>
                    @empty
                        <article class="col-span-full rounded-2xl border border-dashed p-8 text-center text-sm emp-muted" style="border-color: var(--emp-card-border); background: var(--emp-control-bg);">
                            No employee records found for current filters.
                        </article>
                    @endforelse
                </div>
            @endif

            <div class="mt-5">
                {{ $employees->links() }}
            </div>
        </section>

        <section class="emp-breakdown rounded-3xl p-4 md:p-5">
            <div class="flex items-center justify-between gap-3">
                <h4 class="text-lg font-extrabold emp-main-text">Department Breakdown</h4>
                <p class="text-xs uppercase tracking-[0.08em] emp-muted">Live distribution</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($departmentBreakdown as $departmentRow)
                    @php
                        $departmentCount = (int) $departmentRow->employee_count;
                        $barWidth = $maxDepartmentCount > 0
                            ? max(6, (int) round(($departmentCount / $maxDepartmentCount) * 100))
                            : 0;
                    @endphp
                    <div class="rounded-xl border p-3" style="border-color: var(--emp-card-border); background: var(--emp-control-bg);">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <p class="font-semibold emp-main-text">{{ $departmentRow->department_label }}</p>
                            <p class="font-semibold text-sky-400">{{ $departmentCount }}</p>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full" style="background: var(--emp-panel-border);">
                            <div class="emp-breakdown-bar h-2 rounded-full" style="width: {{ $barWidth }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed p-4 text-sm emp-muted" style="border-color: var(--emp-card-border); background: var(--emp-control-bg);">
                        Department distribution will appear once profiles are assigned.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('employeeFiltersForm');
            if (!form) {
                return;
            }

            const searchInput = document.getElementById('employeeSearchInput');
            const selectFilters = form.querySelectorAll('select[data-auto-submit="true"]');
            const submitButton = document.getElementById('employeeFilterSubmit');

            let searchTimer;
            let autoSubmitTimer;

            const markAttention = () => {
                if (submitButton) {
                    submitButton.classList.add('is-attention');
                }
            };

            const clearAttention = () => {
                if (submitButton) {
                    submitButton.classList.remove('is-attention');
                }
            };

            const submitFilters = (delayMs = 200) => {
                window.clearTimeout(autoSubmitTimer);
                autoSubmitTimer = window.setTimeout(() => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, delayMs);
            };

            selectFilters.forEach((field) => {
                field.addEventListener('change', () => {
                    markAttention();
                    submitFilters(120);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    markAttention();
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(() => {
                        submitFilters(0);
                    }, 420);
                });
            }

            form.addEventListener('submit', clearAttention);
        })();
    </script>
@endpush
