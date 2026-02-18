@extends('layouts.dashboard-modern')

@section('title', 'Dashboard')
@section('page_heading', 'Dashboard')

@section('content')
    <section class="ui-hero">
        <div class="flex items-start gap-3">
            <span class="ui-icon-chip ui-icon-violet">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path></svg>
            </span>
            <div>
                <p class="ui-kpi-label">Role-Based Workspace</p>
                @include('dashboard.partials.greeting-header', ['functionalTitle' => 'Role Dashboard'])
            </div>
        </div>
        <p class="ui-section-subtitle">
            Dashboard metrics are rendered from live module records for your role.
        </p>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route(auth()->user()?->dashboardRouteName() ?? 'dashboard') }}" class="ui-btn ui-btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path></svg>
                Continue to My Dashboard
            </a>
            <a href="{{ route('modules.employees.index') }}" class="ui-btn ui-btn-ghost">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                Employee Directory
            </a>
        </div>
    </section>
@endsection
