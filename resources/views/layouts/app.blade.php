<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trim($__env->yieldContent('title', 'Dashboard')) }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $role = $user?->role;
    $roleLabel = $role instanceof \App\Enums\UserRole ? $role->label() : ucfirst((string) $role);
    $canManageUsers = $user?->hasAnyRole([
        \App\Enums\UserRole::ADMIN->value,
        \App\Enums\UserRole::HR->value,
    ]) ?? false;
    $isAdmin = $user?->hasRole(\App\Enums\UserRole::ADMIN->value) ?? false;
    $isSuperAdmin = $user?->hasRole(\App\Enums\UserRole::SUPER_ADMIN->value) ?? false;
    $isEmployee = $user?->hasRole(\App\Enums\UserRole::EMPLOYEE->value) ?? false;
    $dashboardRoute = $user?->dashboardRouteName() ?? 'dashboard';
@endphp
<div class="hrm-app">
    <aside class="hrm-sidebar">
        <div class="hrm-brand">
            <span class="hrm-brand__badge">
                <x-heroicon-o-squares-2x2 class="h-5 w-5 text-gold-400" />
            </span>
            <div>
                <h1 class="hrm-brand__title">{{ config('app.name') }}</h1>
                <p class="hrm-brand__sub">Human Resource Management</p>
            </div>
        </div>

        <nav class="hrm-nav">
            <!-- Main Section -->
            <p class="hrm-nav__title">Main</p>
            <a href="{{ route($dashboardRoute) }}" class="hrm-nav__item {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-squares-2x2 class="h-4 w-4 text-gold-400" /></span>
                <span>Dashboard</span>
            </a>

            <!-- People Section -->
            @if ($user?->can('employees.view') || $user?->can('departments.view') || $user?->can('branches.view'))
                <div class="hrm-nav__divider"></div>
                <p class="hrm-nav__title">People</p>
            @endif
            @can('employees.view')
                <a href="{{ route('modules.employees.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.employees.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-users class="h-4 w-4 text-gold-400" /></span>
                    <span>{{ $isEmployee ? 'Profile' : 'Employees' }}</span>
                </a>
            @endcan
            @can('departments.view')
                <a href="{{ route('modules.departments.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.departments.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-building-office class="h-4 w-4 text-gold-400" /></span>
                    <span>Departments</span>
                </a>
            @endcan
            @can('branches.view')
                <a href="{{ route('modules.branches.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.branches.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-map-pin class="h-4 w-4 text-gold-400" /></span>
                    <span>Branches</span>
                </a>
            @endcan

            <!-- Workforce Section -->
            @if ($user?->can('attendance.view') || $user?->can('leave.view') || $user?->can('holiday.view'))
                <div class="hrm-nav__divider"></div>
                <p class="hrm-nav__title">Workforce</p>
            @endif
            {{-- Attendance Menu --}}
            @can('attendance.view')
                <a href="{{ route('modules.attendance.overview') }}" class="hrm-nav__item {{ request()->routeIs('modules.attendance.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-clock class="h-4 w-4 text-gold-400" /></span>
                    <span>Attendance</span>
                </a>
                <a href="{{ route('modules.attendance.overview') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('modules.attendance.overview') ? 'is-active' : '' }}">
                    <span class="hrm-nav__dot"></span>
                    <span>Overview</span>
                </a>
            @endcan

            {{-- Punch In/Out - For Employees, HR, Finance (not Admin/SuperAdmin) --}}
            @can('attendance.create')
                @if (!$isAdmin && !$isSuperAdmin)
                    <a href="{{ route('modules.attendance.punch') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('modules.attendance.punch') || request()->routeIs('modules.attendance.punch-in') || request()->routeIs('modules.attendance.punch-out') ? 'is-active' : '' }}">
                        <span class="hrm-nav__dot"></span>
                        <span>Punch In/Out</span>
                    </a>
                @endif
            @endcan
            @can('attendance.create')
                @if ($isAdmin || $isSuperAdmin)
                    <a href="{{ route('modules.attendance.overview', ['action' => 'create']) }}" class="hrm-nav__item hrm-nav__subitem">
                        <span class="hrm-nav__dot"></span>
                        <span>Mark Attendance</span>
                    </a>
                @endif
            @endcan
            @can('leave.view')
                <a href="{{ route('modules.leave.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.leave.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-calendar-days class="h-4 w-4 text-gold-400" /></span>
                    <span>Leave</span>
                </a>
            @endcan
            @can('holiday.view')
                <a href="{{ route('modules.holidays.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.holidays.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon">
                        <svg class="h-4 w-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="m9.5 14 1.8 1.8 3.2-3.2"></path>
                        </svg>
                    </span>
                    <span>Holidays</span>
                </a>
            @endcan

            <!-- Finance Section -->
            @can('payroll.view')
                <div class="hrm-nav__divider"></div>
                <p class="hrm-nav__title">Finance</p>
                <a href="{{ route('modules.payroll.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.payroll.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-banknotes class="h-4 w-4 text-gold-400" /></span>
                    <span>Payroll</span>
                </a>
            @endcan

            <!-- Analytics Section -->
            @can('reports.view')
                <div class="hrm-nav__divider"></div>
                <p class="hrm-nav__title">Analytics</p>
                <a href="{{ route('modules.reports.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.reports.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon">
                        <svg class="h-4 w-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path>
                        </svg>
                    </span>
                    <span>Reports</span>
                </a>
                <a href="{{ route('modules.reports.index') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('modules.reports.index') ? 'is-active' : '' }}">
                    <span class="hrm-nav__dot"></span>
                    <span>Overview</span>
                </a>
            @endcan
            @can('reports.activity')
                <a href="{{ route('modules.reports.activity') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('modules.reports.activity') ? 'is-active' : '' }}">
                    <span class="hrm-nav__dot"></span>
                    <span>Activity</span>
                </a>
            @endcan

            @can('settings.view')
                <!-- Settings Section -->
                <div class="hrm-nav__divider"></div>
                <p class="hrm-nav__title">Settings</p>
                <a href="{{ route('settings.index') }}" class="hrm-nav__item {{ request()->routeIs('settings.index') && !request()->has('section') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-cog-6-tooth class="h-4 w-4 text-gold-400" /></span>
                    <span>Overview</span>
                </a>
                @can('settings.company')
                    <a href="{{ route('settings.index', ['section' => 'company']) }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('settings.index') && request()->get('section') === 'company' ? 'is-active' : '' }}">
                        <span class="hrm-nav__icon hrm-nav__icon--small">
                            <x-heroicon-o-building-office-2 class="h-4 w-4 text-gold-400" />
                        </span>
                        <span>Company</span>
                    </a>
                @endcan
                @can('settings.auth_features')
                    <a href="{{ route('settings.index', ['section' => 'system']) }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('settings.index') && request()->get('section') === 'system' ? 'is-active' : '' }}">
                        <span class="hrm-nav__icon hrm-nav__icon--small">
                            <svg class="h-4 w-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <span>System</span>
                    </a>
                @endcan
                @can('settings.smtp')
                    <a href="{{ route('settings.smtp') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('settings.smtp') ? 'is-active' : '' }}">
                        <span class="hrm-nav__icon hrm-nav__icon--small">
                            <x-heroicon-o-envelope class="h-4 w-4 text-gold-400" />
                        </span>
                        <span>SMTP</span>
                    </a>
                @endcan
                @if ($isSuperAdmin)
                    <a href="{{ route('settings.roles-permissions.index') }}" class="hrm-nav__item hrm-nav__subitem {{ request()->routeIs('settings.roles-permissions.*') ? 'is-active' : '' }}">
                        <span class="hrm-nav__icon hrm-nav__icon--small">
                            <svg class="h-4 w-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </span>
                        <span>Roles & Permissions</span>
                    </a>
                @endif
            @endcan
        </nav>

        <div class="hrm-sidebar__foot">
            <p class="hrm-sidebar__foot-label">System Status</p>
            <strong class="hrm-sidebar__foot-title">All Services Operational</strong>
            <span class="hrm-sidebar__foot-meta">Realtime sync active across modules.</span>
        </div>
    </aside>

    <section class="hrm-main">
        <header class="hrm-topbar">
            <div class="hrm-topbar__left">
                <div>
                    <p class="hrm-topbar__meta">{{ strtoupper(trim($__env->yieldContent('page_breadcrumb', 'Workspace'))) }}</p>
                    <h2 class="hrm-topbar__title">@yield('page_heading', 'Dashboard')</h2>
                </div>
            </div>

            <div class="hrm-topbar__right">
                <div class="hrm-user-menu">
                    <button
                        type="button"
                        class="hrm-user-trigger"
                        data-user-menu-toggle
                        aria-expanded="false"
                        aria-controls="user-menu"
                    >
                        <img src="{{ asset('images/user-avatar.svg') }}" alt="User avatar" class="hrm-user-avatar">
                        <span class="hrm-user__meta">
                            <strong class="hrm-user__name">{{ $user?->name }}</strong>
                            <span class="hrm-user__role">{{ $roleLabel }}</span>
                        </span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 text-emerald-900" />
                    </button>

                    <div class="hrm-user-dropdown" id="user-menu" data-user-menu>
                        <a href="{{ route('profile.edit') }}" class="hrm-user-dropdown__item">
                            <x-heroicon-o-pencil-square class="h-4 w-4 text-emerald-900" />
                            Edit Profile
                        </a>
                        @if ($canManageUsers)
                            <a href="{{ route('settings.index') }}" class="hrm-user-dropdown__item">
                                <x-heroicon-o-cog-6-tooth class="h-4 w-4 text-emerald-900" />
                                Settings
                            </a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="hrm-user-dropdown__item hrm-user-dropdown__item--danger">
                                <x-heroicon-o-arrow-right-on-rectangle class="h-4 w-4 text-red-700" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="hrm-content">
            @yield('content')
        </main>

        <footer class="hrm-footer">
            {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </footer>
    </section>
</div>
</body>
</html>
