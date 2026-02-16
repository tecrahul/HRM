@php
    /** @var \Illuminate\Support\Collection<int, array{title:string,meta:string,tone:string,occurred_at:\Illuminate\Support\Carbon}> $recentActivities */
    $activityTitle = $activityTitle ?? 'User Activity';
@endphp

<article class="ui-section">
    <div class="ui-section-head">
        <div class="flex items-center gap-2">
            <span class="ui-icon-chip ui-icon-sky">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3v18h18"></path>
                    <path d="M7 13l3-3 3 2 4-5"></path>
                </svg>
            </span>
            <h3 class="ui-section-title">{{ $activityTitle }}</h3>
        </div>
        <span class="ui-status-chip ui-status-green">Live</span>
    </div>

    <ul class="mt-4 space-y-3">
        @forelse($recentActivities as $activity)
            <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 h-7 w-7 rounded-lg flex items-center justify-center shrink-0" style="background: color-mix(in srgb, {{ $activity['tone'] }} 16%, transparent); color: {{ $activity['tone'] }};">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82"></path>
                            <path d="M4.6 9a1.65 1.65 0 0 0-.33-1.82"></path>
                            <path d="M9 3.8a1.65 1.65 0 0 0-1-1.51"></path>
                        </svg>
                    </span>
                    <div>
                        <p class="font-semibold">{{ $activity['title'] }}</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $activity['meta'] }}</p>
                    </div>
                </div>
            </li>
        @empty
            <li class="ui-empty rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                No recent user activity available.
            </li>
        @endforelse
    </ul>
</article>
