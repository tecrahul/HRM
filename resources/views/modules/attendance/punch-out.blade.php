@extends('layouts.dashboard-modern')

@section('title', 'Punch Out')
@section('page_heading', 'Punch Out')

@section('content')
    <div class="max-w-lg mx-auto">
        <section class="hrm-modern-surface rounded-2xl overflow-hidden border" style="border-color: var(--hr-line);">
            <!-- Compact Header -->
            <div class="px-5 py-4 border-b" style="background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.05) 100%); border-color: var(--hr-line);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-bold" style="color: var(--hr-text-main);">Punch Out</h2>
                            <p class="text-xs" style="color: var(--hr-text-muted);">End your workday</p>
                        </div>
                    </div>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-lg" style="background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                        {{ auth()->user()->full_name }}
                    </span>
                </div>
            </div>

            <!-- Live Clock - Compact -->
            <div class="px-5 py-4 text-center border-b" style="border-color: var(--hr-line);">
                <div class="flex items-center justify-center gap-1.5 mb-2">
                    <span class="h-1.5 w-1.5 bg-orange-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--hr-text-muted);">Live</span>
                </div>
                <div id="live-clock" class="text-3xl font-black" style="color: var(--hr-text-main); font-variant-numeric: tabular-nums;">
                    {{ now()->format('h:i:s A') }}
                </div>
                <div id="live-date" class="text-sm mt-1" style="color: var(--hr-text-muted);">
                    {{ now()->format('l, M j, Y') }}
                </div>
            </div>

            <!-- Work Summary -->
            @if(isset($todayAttendance) && $todayAttendance && $todayAttendance->check_in_at)
            <div class="px-5 py-3 border-b flex items-center justify-between" style="background: rgba(59, 130, 246, 0.06); border-color: var(--hr-line);">
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--hr-text-muted);">Check In</p>
                        <p class="text-sm font-bold" style="color: var(--hr-text-main);">{{ $todayAttendance->check_in_at->format('h:i A') }}</p>
                    </div>
                    <div class="h-6 w-px" style="background: var(--hr-line);"></div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--hr-text-muted);">Duration</p>
                        <p class="text-sm font-bold" style="color: #3b82f6;" id="work-duration">--</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('modules.attendance.check-out') }}" class="px-5 py-4">
                @csrf

                <div class="mb-4">
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--hr-text-muted);">
                        Notes (Optional)
                    </label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        class="w-full rounded-xl border px-3 py-2 text-sm bg-transparent resize-none"
                        style="border-color: var(--hr-line);"
                        placeholder="Tasks completed, early departure reason, etc."
                    ></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12l5 5L20 7"></path>
                        </svg>
                        Punch Out
                    </button>
                    <a href="{{ route('modules.attendance.overview') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold border" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
                        Cancel
                    </a>
                </div>
            </form>

            <!-- Compact Footer -->
            <div class="px-5 py-3 border-t" style="background: var(--hr-surface-strong); border-color: var(--hr-line);">
                <p class="text-[11px] leading-relaxed" style="color: var(--hr-text-muted);">
                    Your check-out time will be recorded. Early departures may require approval.
                </p>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        (function() {
            function updateClock() {
                const now = new Date();
                const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                const date = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
                document.getElementById('live-clock').textContent = time;
                document.getElementById('live-date').textContent = date;
            }
            updateClock();
            setInterval(updateClock, 1000);

            @if(isset($todayAttendance) && $todayAttendance && $todayAttendance->check_in_at)
            function updateDuration() {
                const checkIn = new Date('{{ $todayAttendance->check_in_at->toIso8601String() }}');
                const now = new Date();
                const diff = now - checkIn;
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const el = document.getElementById('work-duration');
                if (el) el.textContent = hours + 'h ' + minutes + 'm';
            }
            updateDuration();
            setInterval(updateDuration, 60000);
            @endif
        })();
    </script>
    @endpush
@endsection
