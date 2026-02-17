@extends('layouts.dashboard-modern')

@section('title', 'Notifications')
@section('page_heading', 'Notifications')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <p class="ui-kpi-label">Total</p>
            <p class="ui-kpi-value">{{ $stats['total'] }}</p>
        </article>
        <article class="ui-kpi-card">
            <p class="ui-kpi-label">Unread</p>
            <p class="ui-kpi-value">{{ $stats['unread'] }}</p>
        </article>
        <article class="ui-kpi-card">
            <p class="ui-kpi-label">Read</p>
            <p class="ui-kpi-value">{{ $stats['read'] }}</p>
        </article>
        <article class="ui-kpi-card flex items-center justify-end">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PUT')
                <button type="submit" class="ui-btn ui-btn-primary">Mark All Read</button>
            </form>
        </article>
    </section>

    <section class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">All Notifications</h3>
                <p class="ui-section-subtitle">Read and unread alerts based on your role and activity.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('notifications.index') }}" class="mt-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('notifications.index', ['status' => 'all']) }}" class="ui-btn {{ $status === 'all' ? 'ui-btn-primary' : 'ui-btn-ghost' }}">All</a>
            <a href="{{ route('notifications.index', ['status' => 'unread']) }}" class="ui-btn {{ $status === 'unread' ? 'ui-btn-primary' : 'ui-btn-ghost' }}">Unread</a>
            <a href="{{ route('notifications.index', ['status' => 'read']) }}" class="ui-btn {{ $status === 'read' ? 'ui-btn-primary' : 'ui-btn-ghost' }}">Read</a>
        </form>

        <div class="mt-4 space-y-3">
            @forelse($notifications as $notification)
                @php
                    $payload = (array) $notification->data;
                    $title = (string) ($payload['title'] ?? 'Notification');
                    $message = (string) ($payload['message'] ?? '');
                    $url = (string) ($payload['url'] ?? '');
                    $isUnread = $notification->read_at === null;
                @endphp
                <article class="rounded-xl border p-4 {{ $isUnread ? 'border-amber-300' : '' }}" style="background: var(--hr-surface-strong); border-color: var(--hr-line);">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $title }}</p>
                            <p class="text-sm mt-1" style="color: var(--hr-text-muted);">{{ $message }}</p>
                            <p class="text-xs mt-2" style="color: var(--hr-text-muted);">
                                {{ $notification->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                            </p>
                        </div>
                        <span class="ui-status-chip {{ $isUnread ? 'ui-status-amber' : 'ui-status-slate' }}">
                            {{ $isUnread ? 'Unread' : 'Read' }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($url !== '')
                            <a href="{{ route('notifications.open', $notification->id) }}" class="ui-btn ui-btn-ghost">Open</a>
                        @endif

                        @if ($isUnread)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="ui-btn ui-btn-primary">Mark Read</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('notifications.unread', $notification->id) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="ui-btn ui-btn-ghost">Mark Unread</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="ui-empty">No notifications found.</p>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </section>
@endsection
