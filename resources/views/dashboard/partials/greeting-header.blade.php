@php
    $viewerName = trim((string) (auth()->user()?->name ?? ''));
    $viewerFirstName = trim((string) str($viewerName)->before(' '));
    if ($viewerFirstName === '') {
        $viewerFirstName = 'there';
    }

    $functionalTitle = (string) ($functionalTitle ?? '');
    $showWave = (bool) ($showWave ?? true);

    $hour = now()->hour;
    if ($hour >= 5 && $hour <= 11) {
        $serverGreeting = 'Good morning';
    } elseif ($hour >= 12 && $hour <= 16) {
        $serverGreeting = 'Good afternoon';
    } else {
        $serverGreeting = 'Good evening';
    }

    $serverGreetingText = $serverGreeting.', '.$viewerFirstName;
@endphp

<div
    data-dashboard-greeting-root
    data-first-name="{{ $viewerFirstName }}"
    data-functional-title="{{ $functionalTitle }}"
    data-show-wave="{{ $showWave ? '1' : '0' }}"
>
    <h2 class="mt-2 text-2xl md:text-3xl font-extrabold tracking-tight">
        {{ $serverGreetingText }}
        @if($showWave)
            <span class="ml-2 align-middle" role="img" aria-label="waving hand">👋</span>
        @endif
    </h2>
    @if($functionalTitle !== '')
        <p class="mt-2 text-xs font-semibold uppercase tracking-[0.1em]" style="color: var(--hr-text-muted);">
            {{ $functionalTitle }}
        </p>
    @endif
</div>
