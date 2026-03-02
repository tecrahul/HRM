@extends('layouts.dashboard-modern')

@section('title', 'Punch In')
@section('page_heading', 'Punch In')

@section('content')
    <div class="ui-section">
        <div class="ui-section-head">
            <div>
                <h3 class="ui-section-title">Attendance</h3>
                <p class="ui-section-subtitle">Clock in to start your workday</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('modules.attendance.overview') }}" class="ui-btn ui-btn-ghost">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Back to Overview
                </a>
            </div>
        </div>

        <div class="max-w-2xl mx-auto mt-8">
            <!-- Professional Punch In Card -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <!-- Header with gradient -->
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                                <x-heroicon-o-clock class="h-8 w-8 text-white" />
                            </div>
                            <div class="text-white">
                                <h2 class="text-2xl font-bold">Punch In</h2>
                                <p class="text-emerald-100 text-sm mt-1">Start your workday</p>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20">
                            <span class="text-white text-xs font-medium">{{ auth()->user()->full_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Live Clock Display -->
                <div class="bg-gradient-to-b from-gray-50 to-white px-8 py-6 border-b border-gray-200">
                    <div class="text-center">
                        <div class="inline-flex items-center gap-2 mb-3">
                            <div class="h-2 w-2 bg-emerald-500 rounded-full animate-pulse"></div>
                            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Current Time</span>
                        </div>
                        <div id="live-clock" class="text-4xl font-bold text-gray-900 mb-2" style="font-variant-numeric: tabular-nums;">
                            {{ now()->format('h:i:s A') }}
                        </div>
                        <div id="live-date" class="text-base text-gray-600 mb-2">
                            {{ now()->format('l, F j, Y') }}
                        </div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-full">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span id="timezone-display" class="text-sm font-medium text-gray-700">
                                {{ config('app.timezone') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form method="POST" action="{{ route('modules.attendance.check-in') }}" class="px-8 py-6">
                    @csrf

                    <!-- Notes Field -->
                    <div class="mb-6">
                        <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-pencil-square class="h-4 w-4 text-gray-500" />
                                Notes (Optional)
                            </div>
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="ui-textarea resize-none"
                            placeholder="Add any notes for your check-in (e.g., working from home, early start, etc.)"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1.5">This note will be attached to your attendance record.</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-semibold py-3.5 px-6 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <x-heroicon-o-check class="h-5 w-5" />
                            Confirm Punch In
                        </button>
                        <a href="{{ route('modules.attendance.overview') }}" class="px-6 py-3.5 border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold rounded-lg transition-all duration-200 flex items-center gap-2">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                            Cancel
                        </a>
                    </div>
                </form>

                <!-- Footer Info -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                    <div class="flex items-start gap-3">
                        <div class="bg-blue-100 rounded-lg p-2 mt-0.5">
                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-medium text-gray-700 mb-1">Attendance Policy</p>
                            <p class="text-xs text-gray-600 leading-relaxed">Make sure to punch in at the start of your shift. Late check-ins may require manager approval. Your timestamp will be recorded with your current location and timezone.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Live clock update
        function updateClock() {
            const now = new Date();

            // Time with seconds
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const timeString = now.toLocaleTimeString('en-US', timeOptions);

            // Full date
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const dateString = now.toLocaleDateString('en-US', dateOptions);

            // Update DOM
            document.getElementById('live-clock').textContent = timeString;
            document.getElementById('live-date').textContent = dateString;
        }

        // Update immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
    </script>
    @endpush
@endsection
