@extends('layouts.dashboard-modern')

@section('title', 'Punch In')
@section('page_heading', 'Punch In')

@section('content')
    <div class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Start Your Day</h3>
                <p class="ui-section-subtitle">Record your check-in time with an optional note.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('modules.attendance.overview') }}" class="ui-btn ui-btn-ghost">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Back to Attendance
                </a>
            </div>
        </div>

        <div class="rounded-xl border p-3 text-xs font-semibold mb-3" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
            Current server time: {{ now()->format('M d, Y h:i A') }}
        </div>

        <form method="POST" action="{{ route('modules.attendance.check-in') }}" class="mt-5 max-w-xl space-y-3">
            @csrf
            <div>
                <label for="notes" class="block text-sm font-semibold mb-1">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" class="ui-textarea" placeholder="Anything to note for today?"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">
                    <x-heroicon-o-check class="h-4 w-4" />
                    Confirm Punch In
                </button>
                <a href="{{ route('modules.attendance.overview') }}" class="ui-btn ui-btn-ghost">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
