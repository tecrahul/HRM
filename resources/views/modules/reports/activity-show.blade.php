@extends('layouts.dashboard-modern')

@section('title', 'Activity Details')
@section('page_heading', 'Activity Details')

@section('content')
    @php
        $subjectType = (string) ($activity->subject_type ?? '');
        $subjectTypeLabel = $subjectType !== '' ? class_basename($subjectType) : 'N/A';
        $actorRole = $activity->actor?->role;
        $actorRoleLabel = $actorRole instanceof \App\Enums\UserRole ? $actorRole->label() : ucfirst((string) $actorRole);
    @endphp

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Activity Snapshot</h3>
                <p class="ui-section-subtitle">Detailed audit information for selected activity record.</p>
            </div>
            <a href="{{ $backUrl }}" class="ui-btn ui-btn-ghost">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                Back to Activity Log
            </a>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Occurred At</p>
                <p class="mt-1 font-semibold">{{ $activity->occurred_at?->format('M d, Y h:i A') ?? 'N/A' }}</p>
            </article>
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Event Key</p>
                <p class="mt-1 font-semibold">{{ $activity->event_key ?: 'N/A' }}</p>
            </article>
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Actor</p>
                <p class="mt-1 font-semibold">{{ $activity->actor?->name ?? 'System' }}</p>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">
                    {{ $activity->actor?->email ?? 'No direct user' }}
                    @if ($activity->actor)
                        • {{ $actorRoleLabel }}
                    @endif
                </p>
            </article>
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Subject Type</p>
                <p class="mt-1 font-semibold">{{ $subjectTypeLabel }}</p>
            </article>
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Subject ID</p>
                <p class="mt-1 font-semibold">{{ $activity->subject_id ?: 'N/A' }}</p>
            </article>
            <article class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Record ID</p>
                <p class="mt-1 font-semibold">{{ $activity->id }}</p>
            </article>
        </div>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Message</h3>
                <p class="ui-section-subtitle">Primary activity details captured by the system.</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
            <p class="font-semibold">{{ $activity->title ?: 'N/A' }}</p>
            <p class="text-sm mt-2" style="color: var(--hr-text-muted);">{{ $activity->meta ?: 'N/A' }}</p>
        </div>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Payload</h3>
                <p class="ui-section-subtitle">Raw structured payload stored with this activity.</p>
            </div>
        </div>

        <div class="ui-table-wrap mt-4">
            <pre class="p-4 text-xs leading-relaxed whitespace-pre-wrap" style="color: var(--hr-text-main);">{{ $payloadJson }}</pre>
        </div>
    </section>
@endsection
