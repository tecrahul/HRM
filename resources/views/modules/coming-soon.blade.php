@extends('layouts.dashboard-modern')

@section('title', $moduleName)
@section('page_heading', $moduleName . ' Module')

@section('content')
    <section class="hrm-modern-surface rounded-2xl p-6">
        <div class="flex items-start gap-3">
            <span class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" style="background: rgb(245 158 11 / 0.16); color: #d97706;">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86l-7.2 12.47A2 2 0 0 0 4.8 19.33h14.4a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
            </span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em]" style="color: var(--hr-text-muted);">Module Status</p>
                <h3 class="mt-2 text-2xl font-extrabold">{{ $moduleName }} is Not Available</h3>
            </div>
        </div>
        <p class="mt-2 text-sm leading-relaxed" style="color: var(--hr-text-muted);">
            This module is currently not enabled in the system. No operational records are available on this page.
        </p>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route(auth()->user()?->dashboardRouteName() ?? 'dashboard') }}" class="ui-btn ui-btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path></svg>
                Back to Dashboard
            </a>
            <a href="{{ route('settings.index') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82"></path></svg>
                Open Settings
            </a>
        </div>
    </section>
@endsection
