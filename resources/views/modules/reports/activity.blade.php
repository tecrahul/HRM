@extends('layouts.dashboard-modern')

@section('title', 'Report Activity')
@section('page_heading', 'Report Activity')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please review activity filters and try again.</div>
    @endif

    <section class="ui-section">
        <form method="GET" action="{{ route('modules.reports.activity') }}" class="space-y-4">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Recent Activity</h3>
                    <p class="ui-section-subtitle">Audit trail for {{ $periodLabel }}</p>
                </div>

                <div class="w-full sm:w-auto grid grid-cols-1 sm:grid-cols-2 gap-2 sm:min-w-[360px]">
                    <div>
                        <label for="activity_from_date" class="ui-kpi-label block mb-2">From Date</label>
                        <input
                            id="activity_from_date"
                            type="date"
                            name="from_date"
                            value="{{ $filters['from_date'] }}"
                            class="ui-input"
                            title="From date"
                        >
                    </div>
                    <div>
                        <label for="activity_to_date" class="ui-kpi-label block mb-2">To Date</label>
                        <input
                            id="activity_to_date"
                            type="date"
                            name="to_date"
                            value="{{ $filters['to_date'] }}"
                            class="ui-input"
                            title="To date"
                        >
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-4 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <p class="ui-kpi-label">Activity Filters</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="activity_q" class="ui-kpi-label block mb-2">Search</label>
                        <input
                            id="activity_q"
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Title, meta, event key, actor"
                            class="ui-input"
                        >
                    </div>

                    <div>
                        <label for="activity_event_key" class="ui-kpi-label block mb-2">Event Key</label>
                        <select id="activity_event_key" name="event_key" class="ui-select">
                            <option value="">All Events</option>
                            @foreach($eventKeyOptions as $eventKey)
                                <option value="{{ $eventKey }}" {{ $filters['event_key'] === $eventKey ? 'selected' : '' }}>
                                    {{ $eventKey }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($isManagement)
                        <div>
                            <label for="activity_actor_user_id" class="ui-kpi-label block mb-2">Actor</label>
                            <select id="activity_actor_user_id" name="actor_user_id" class="ui-select">
                                <option value="">All Actors</option>
                                @foreach($actorOptions as $actor)
                                    <option value="{{ $actor->id }}" {{ (int) $filters['actor_user_id'] === (int) $actor->id ? 'selected' : '' }}>
                                        {{ $actor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border p-3 flex flex-wrap items-center gap-2" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <button type="submit" class="ui-btn ui-btn-primary">Apply Filters</button>
                <a href="{{ route('modules.reports.activity') }}" class="ui-btn ui-btn-ghost">Clear Filters</a>
            </div>
        </form>
    </section>

    <section class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Total Activity</p>
                    <p class="ui-kpi-value">{{ $stats['total'] }}</p>
                    <p class="ui-kpi-meta">Matching selected filters</p>
                </div>
                <span class="ui-icon-chip ui-icon-pink">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Unique Actors</p>
                    <p class="ui-kpi-value">{{ $stats['uniqueActors'] }}</p>
                    <p class="ui-kpi-meta">Users who generated activity</p>
                </div>
                <span class="ui-icon-chip ui-icon-blue">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>

        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">System Events</p>
                    <p class="ui-kpi-value">{{ $stats['systemEvents'] }}</p>
                    <p class="ui-kpi-meta">Events without direct actor</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="ui-section mt-5">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Activity Log</h3>
                <p class="ui-section-subtitle">Paginated view of tracked events for the selected date range.</p>
            </div>
        </div>

        <div class="ui-table-wrap">
            <table class="ui-table" style="min-width: 1050px;">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Event Key</th>
                    <th>Title</th>
                    <th>Actor</th>
                    <th>Meta</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td>{{ $activity->occurred_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                        <td>
                            <span class="ui-status-chip ui-status-slate">{{ $activity->event_key }}</span>
                        </td>
                        <td>{{ $activity->title }}</td>
                        <td>{{ $activity->actor?->name ?? 'System' }}</td>
                        <td>{{ $activity->meta ?? 'N/A' }}</td>
                        <td>
                            <a
                                href="{{ route('modules.reports.activity.show', array_merge(['activity' => $activity->id], request()->query())) }}"
                                class="ui-btn ui-btn-ghost"
                                aria-label="View activity details"
                                title="View activity details"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ui-empty">No activity found for the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </section>
@endsection
