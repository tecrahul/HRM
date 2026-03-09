@extends('layouts.dashboard-modern')

@section('title', 'Roles & Permissions')
@section('page_heading', 'Roles & Permissions')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <section class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold" style="color:var(--hr-text-main)">Roles & Permissions</h2>
            <p class="text-sm mt-1" style="color:var(--hr-text-muted)">
                Manage user roles and their permissions. Super admin can assign granular permissions to each role.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <form action="{{ route('settings.roles-permissions.sync-users') }}" method="POST">
                @csrf
                <button type="submit" class="ui-btn ui-btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync Users
                </button>
            </form>
            <form action="{{ route('settings.roles-permissions.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="ui-btn ui-btn-ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Clear Cache
                </button>
            </form>
            <a href="{{ route('settings.roles-permissions.health') }}" class="ui-btn ui-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                System Health
            </a>
        </div>
    </section>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold flex items-center gap-3"
             style="border-color:rgb(16 185 129 / 0.45);background:rgb(16 185 129 / 0.1);color:rgb(6 95 70)">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border px-4 py-3 text-sm font-semibold flex items-center gap-3"
             style="border-color:rgb(239 68 68 / 0.45);background:rgb(239 68 68 / 0.1);color:rgb(153 27 27)">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statCards = [
                ['label' => 'Total Roles',          'value' => $totalRoles,                       'color' => 'var(--hr-accent)'],
                ['label' => 'Total Permissions',    'value' => $totalPermissions,                 'color' => '#10b981'],
                ['label' => 'Users with Roles',     'value' => $roles->sum('users_count'),        'color' => '#3b82f6'],
                ['label' => 'Permission Modules',   'value' => $permissionsByModule->count(),     'color' => '#a855f7'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="hrm-modern-surface rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] truncate" style="color:var(--hr-text-muted)">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold" style="color:{{ $card['color'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Roles Table --}}
    <div class="hrm-modern-surface rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color:var(--hr-line)">
            <div>
                <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Roles</h3>
                <p class="text-sm mt-0.5" style="color:var(--hr-text-muted)">Manage user roles and their associated permissions</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--hr-line);color:var(--hr-text-muted)">
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Users</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Guard</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr class="border-b transition-colors" style="border-color:var(--hr-line)">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl flex items-center justify-center text-xs font-extrabold shrink-0"
                                         style="background:var(--hr-accent-soft);color:var(--hr-accent)">
                                        {{ strtoupper(substr($role->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold" style="color:var(--hr-text-main)">{{ ucwords(str_replace('_', ' ', $role->name)) }}</p>
                                        <p class="text-xs font-mono" style="color:var(--hr-text-muted)">{{ $role->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      style="background:rgb(16 185 129 / 0.12);color:rgb(6 95 70)">
                                    {{ $role->permissions_count }} permissions
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      style="background:rgb(59 130 246 / 0.12);color:rgb(30 64 175)">
                                    {{ $role->users_count }} users
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono" style="color:var(--hr-text-muted)">{{ $role->guard_name }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('settings.roles-permissions.show-role', $role) }}"
                                       class="text-xs font-semibold" style="color:var(--hr-accent)">View</a>
                                    <a href="{{ route('settings.roles-permissions.edit-role', $role) }}"
                                       class="text-xs font-semibold" style="color:var(--hr-accent)">Edit Permissions</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm" style="color:var(--hr-text-muted)">
                                No roles found. Run RBAC seeder to create roles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Permissions by Module --}}
    <div class="hrm-modern-surface rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b" style="border-color:var(--hr-line)">
            <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Permissions by Module</h3>
            <p class="text-sm mt-0.5" style="color:var(--hr-text-muted)">All permissions organised by module</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($permissionsByModule as $module => $modulePermissions)
                    <div class="rounded-xl border p-4" style="border-color:var(--hr-line);background:var(--hr-surface-strong)">
                        <h4 class="text-xs font-extrabold uppercase tracking-[0.08em] mb-3 flex items-center gap-2" style="color:var(--hr-text-main)">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" style="color:var(--hr-accent)">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ ucfirst($module) }}
                            <span class="ml-auto font-normal text-[11px]" style="color:var(--hr-text-muted)">({{ $modulePermissions->count() }})</span>
                        </h4>
                        <div class="space-y-1.5">
                            @foreach($modulePermissions->take(5) as $permission)
                                <div class="text-xs flex items-center gap-1.5" style="color:var(--hr-text-muted)">
                                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ str_replace($module . '.', '', $permission->name) }}
                                </div>
                            @endforeach
                            @if($modulePermissions->count() > 5)
                                <div class="text-xs font-semibold mt-2" style="color:var(--hr-accent)">
                                    +{{ $modulePermissions->count() - 5 }} more…
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
