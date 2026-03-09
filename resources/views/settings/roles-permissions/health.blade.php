@extends('layouts.dashboard-modern')

@section('title', 'RBAC System Health')
@section('page_heading', 'RBAC System Health')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb + Header --}}
    <section class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs mb-3" style="color:var(--hr-text-muted)">
                <a href="{{ route('settings.roles-permissions.index') }}" class="hover:underline" style="color:var(--hr-accent)">Roles & Permissions</a>
                <span>/</span>
                <span>System Health</span>
            </nav>
            <h2 class="text-xl font-extrabold" style="color:var(--hr-text-main)">RBAC System Health</h2>
            <p class="text-sm mt-1" style="color:var(--hr-text-muted)">Check the health and configuration of the RBAC system</p>
        </div>
        @if($isHealthy)
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold shrink-0"
                  style="background:rgb(16 185 129 / 0.12);color:rgb(6 95 70)">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                System Healthy
            </span>
        @else
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold shrink-0"
                  style="background:rgb(239 68 68 / 0.12);color:rgb(153 27 27)">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                Issues Found
            </span>
        @endif
    </section>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statCards = [
                ['label' => 'Total Roles',             'value' => $stats['total_roles'],                      'color' => 'var(--hr-accent)'],
                ['label' => 'Total Permissions',       'value' => $stats['total_permissions'],                'color' => '#10b981'],
                ['label' => 'Users with Roles',        'value' => $stats['total_users_with_roles'],           'color' => '#3b82f6'],
                ['label' => 'Role-Permission Links',   'value' => $stats['total_role_permission_assignments'],'color' => '#a855f7'],
            ];
        @endphp
        @foreach($statCards as $card)
            <div class="hrm-modern-surface rounded-2xl p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] truncate" style="color:var(--hr-text-muted)">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold" style="color:{{ $card['color'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Health Report --}}
    <div class="hrm-modern-surface rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b" style="border-color:var(--hr-line)">
            <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Health Check Report</h3>
            <p class="text-sm mt-0.5" style="color:var(--hr-text-muted)">Detailed system health analysis</p>
        </div>
        <div class="p-6">
            <pre class="rounded-xl p-5 overflow-x-auto text-sm font-mono leading-relaxed"
                 style="background:#0d1117;color:#7ee787">{{ $output }}</pre>
        </div>
    </div>

</div>
@endsection
