@extends('layouts.dashboard-modern')

@section('title', ucwords(str_replace('_', ' ', $role->name)) . ' Role')
@section('page_heading', ucwords(str_replace('_', ' ', $role->name)) . ' Role')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb + Header --}}
    <section class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs mb-3" style="color:var(--hr-text-muted)">
                <a href="{{ route('settings.roles-permissions.index') }}" class="hover:underline" style="color:var(--hr-accent)">Roles & Permissions</a>
                <span>/</span>
                <span>{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
            </nav>
            <h2 class="text-xl font-extrabold" style="color:var(--hr-text-main)">{{ ucwords(str_replace('_', ' ', $role->name)) }} Role</h2>
            <p class="text-sm mt-1" style="color:var(--hr-text-muted)">View permissions assigned to this role</p>
        </div>
        <a href="{{ route('settings.roles-permissions.edit-role', $role) }}" class="ui-btn ui-btn-primary shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Permissions
        </a>
    </section>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @php
            $statCards = [
                ['label' => 'Total Permissions',   'value' => $role->permissions_count, 'color' => '#10b981'],
                ['label' => 'Assigned Users',       'value' => $role->users_count,       'color' => '#3b82f6'],
                ['label' => 'Permission Modules',   'value' => $permissionsByModule->count(), 'color' => '#a855f7'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="hrm-modern-surface rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color:var(--hr-text-muted)">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold" style="color:{{ $card['color'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Permissions by Module --}}
    <div class="hrm-modern-surface rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b" style="border-color:var(--hr-line)">
            <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Permissions by Module</h3>
        </div>
        <div class="p-6 space-y-6">
            @forelse($permissionsByModule as $module => $modulePermissions)
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-[0.08em] mb-3 flex items-center gap-2 pb-2 border-b" style="color:var(--hr-text-main);border-color:var(--hr-line)">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" style="color:var(--hr-accent)">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ ucfirst($module) }}
                        <span class="ml-auto font-normal" style="color:var(--hr-text-muted)">({{ $modulePermissions->count() }})</span>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($modulePermissions as $permission)
                            <div class="flex items-center gap-2 text-xs rounded-lg px-3 py-2" style="background:var(--hr-surface-strong);color:var(--hr-text-main)">
                                <svg class="w-3.5 h-3.5 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $permission->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-center py-4" style="color:var(--hr-text-muted)">No permissions assigned to this role.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
