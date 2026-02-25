@extends('layouts.dashboard-modern')

@section('title', 'Review Leave Request')
@section('page_heading', 'Review Leave Request')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please review the form and fix validation errors.</div>
    @endif

    @php
        $profile = $leaveRequest->user?->profile;
        $isHalfDay = ($leaveRequest->day_type ?? null) === 'half_day' || $leaveRequest->leave_type === 'half_day';
    @endphp

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Pending Request Details</h3>
                    <p class="ui-section-subtitle">Review the request and capture your note before final action.</p>
                </div>
                <span class="ui-status-chip ui-status-amber">Pending</span>
            </div>

            <div class="mt-4 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="ui-kpi-label">Employee</p>
                        <p class="font-semibold">{{ $leaveRequest->user?->name ?? 'N/A' }}</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $leaveRequest->user?->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="ui-kpi-label">Department / Branch</p>
                        <p class="font-semibold">{{ $profile?->department ?? 'N/A' }} / {{ $profile?->branch ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="ui-kpi-label">Leave Type</p>
                        <p class="font-semibold">
                            {{ str($leaveRequest->leave_type)->replace('_', ' ')->title() }}
                            @if ($isHalfDay)
                                (Half Day{{ $leaveRequest->half_day_session ? ' - ' . str($leaveRequest->half_day_session)->replace('_', ' ')->title() : '' }})
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="ui-kpi-label">Duration</p>
                        <p class="font-semibold">{{ $leaveRequest->start_date?->format('M d, Y') }} to {{ $leaveRequest->end_date?->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="ui-kpi-label">Total Days</p>
                        <p class="font-semibold">{{ number_format((float) $leaveRequest->total_days, 1) }}</p>
                    </div>
                    <div>
                        <p class="ui-kpi-label">Requested On</p>
                        <p class="font-semibold">{{ $leaveRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="ui-kpi-label">Reason</p>
                    <p class="text-sm mt-1">{{ $leaveRequest->reason }}</p>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Review Decision</h3>
                    <p class="ui-section-subtitle">Note is mandatory when rejecting.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.leave.review', $leaveRequest) }}" class="mt-4 space-y-3" id="leaveReviewForm">
                @csrf
                @method('PUT')

                <div>
                    <label for="review_note" class="ui-kpi-label block mb-2">Review Note</label>
                    <textarea
                        id="review_note"
                        name="review_note"
                        rows="5"
                        class="ui-textarea"
                        placeholder="Add a clear decision note for this request..."
                    >{{ old('review_note') }}</textarea>
                    @error('review_note')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p id="reviewNoteHint" class="text-xs mt-1 hidden text-red-600">Please add a note before rejecting this request.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" name="status" value="approved" class="ui-btn" style="background: #16a34a; color: #fff; border-color: transparent;">
                        <x-heroicon-o-check class="h-4 w-4" />
                        Approve Request
                    </button>
                    <button type="submit" name="status" value="rejected" class="ui-btn" style="background: #dc2626; color: #fff; border-color: transparent;">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                        Reject Request
                    </button>
                    <a href="{{ route('modules.leave.index') }}" class="ui-btn ui-btn-ghost">
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        Back to Leave
                    </a>
                </div>
            </form>
        </article>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('leaveReviewForm');
            const note = document.getElementById('review_note');
            const hint = document.getElementById('reviewNoteHint');

            if (!form || !note || !hint) {
                return;
            }

            form.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                const isReject = submitter && submitter.name === 'status' && submitter.value === 'rejected';
                const hasNote = note.value.trim().length > 0;

                hint.classList.add('hidden');

                if (isReject && !hasNote) {
                    event.preventDefault();
                    hint.classList.remove('hidden');
                    note.focus();
                }
            });
        })();
    </script>
@endpush
