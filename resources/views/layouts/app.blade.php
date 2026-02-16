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
            <p class="hrm-nav__title">Navigation</p>
            <a href="{{ route($dashboardRoute) }}" class="hrm-nav__item {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-squares-2x2 class="h-4 w-4 text-gold-400" /></span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('modules.employees.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.employees.*') ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-users class="h-4 w-4 text-gold-400" /></span>
                <span>{{ $isEmployee ? 'Profile' : 'Employees' }}</span>
            </a>
            @if ($canManageUsers)
                <a href="{{ route('modules.departments.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.departments.*') ? 'is-active' : '' }}">
                    <span class="hrm-nav__icon"><x-heroicon-o-users class="h-4 w-4 text-gold-400" /></span>
                    <span>Departments</span>
                </a>
                @if ($isAdmin)
                    <a href="{{ route('modules.branches.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.branches.*') ? 'is-active' : '' }}">
                        <span class="hrm-nav__icon"><x-heroicon-o-users class="h-4 w-4 text-gold-400" /></span>
                        <span>Branches</span>
                    </a>
                @endif
            @endif
            <a href="{{ route('modules.attendance.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.attendance.*') ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-clock class="h-4 w-4 text-gold-400" /></span>
                <span>Attendance</span>
            </a>
            <a href="{{ route('modules.payroll.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.payroll.*') ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-banknotes class="h-4 w-4 text-gold-400" /></span>
                <span>Payroll</span>
            </a>
            <a href="{{ route('modules.leave.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.leave.*') ? 'is-active' : '' }}">
                <span class="hrm-nav__icon"><x-heroicon-o-calendar-days class="h-4 w-4 text-gold-400" /></span>
                <span>Leave</span>
            </a>
            <a href="{{ route('modules.reports.index') }}" class="hrm-nav__item {{ request()->routeIs('modules.reports.*') ? 'is-active' : '' }}">
                <span class="hrm-nav__icon">
                    <svg class="h-4 w-4 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path>
                    </svg>
                </span>
                <span>Reports</span>
            </a>
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
                    <h2 class="hrm-topbar__title">@yield('page_heading', 'Dashboard')</h2>
                    <p class="hrm-topbar__meta">Enterprise HR workspace</p>
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
