@extends('layouts.dashboard-modern')

@section('title', 'My Employee Profile')
@section('page_heading', 'Employee Profile')

@section('content')
    @php
        $employmentTypeLabel = str((string) ($profile?->employment_type ?? 'full_time'))->replace('_', ' ')->title();
        $statusLabel = ucfirst((string) ($profile?->status ?? 'active'));
    @endphp

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Profile Completion</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $profileCompletion }}%</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Employment Type</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ $employmentTypeLabel }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(124 58 237 / 0.16); color: #7c3aed;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Status</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ $statusLabel }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M9 12l2 2 4-4"></path></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Joined On</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ $profile?->joined_on?->format('M d, Y') ?? 'Not Set' }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold">My Employee Details</h3>
                        <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Personal and employment information visible to HR operations.</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-primary">
                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                    Update Profile
                </a>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Full Name</p>
                    <p class="mt-1 font-semibold">{{ $viewer->name }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Email</p>
                    <p class="mt-1 font-semibold">{{ $viewer->email }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Phone</p>
                    <p class="mt-1 font-semibold">{{ $profile?->phone ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Alternate Phone</p>
                    <p class="mt-1 font-semibold">{{ $profile?->alternate_phone ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Department</p>
                    <p class="mt-1 font-semibold">{{ $profile?->department ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Branch</p>
                    <p class="mt-1 font-semibold">{{ $profile?->branch ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Job Title</p>
                    <p class="mt-1 font-semibold">{{ $profile?->job_title ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Emergency Contact</p>
                    <p class="mt-1 font-semibold">{{ $profile?->emergency_contact_name ?? 'Not provided' }}</p>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $profile?->emergency_contact_phone ?? 'No phone on file' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Date of Birth</p>
                    <p class="mt-1 font-semibold">{{ $profile?->date_of_birth?->format('M d, Y') ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Gender</p>
                    <p class="mt-1 font-semibold">{{ str((string) ($profile?->gender ?? 'Not provided'))->replace('_', ' ')->title() }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Marital Status</p>
                    <p class="mt-1 font-semibold">{{ str((string) ($profile?->marital_status ?? 'Not provided'))->replace('_', ' ')->title() }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Nationality</p>
                    <p class="mt-1 font-semibold">{{ $profile?->nationality ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">National ID / Passport</p>
                    <p class="mt-1 font-semibold">{{ $profile?->national_id ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Work Location</p>
                    <p class="mt-1 font-semibold">{{ $profile?->work_location ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Reporting Manager</p>
                    <p class="mt-1 font-semibold">{{ $profile?->manager_name ?? 'Not provided' }}</p>
                </div>
                <div class="rounded-xl border p-3 md:col-span-2" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">LinkedIn</p>
                    @if (! blank($profile?->linkedin_url))
                        <a href="{{ $profile->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex font-semibold" style="color: var(--hr-accent);">
                            {{ $profile->linkedin_url }}
                        </a>
                    @else
                        <p class="mt-1 font-semibold">Not provided</p>
                    @endif
                </div>
                <div class="rounded-xl border p-3 md:col-span-2" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Address</p>
                    <p class="mt-1 font-semibold">{{ $profile?->address ?? 'No address available.' }}</p>
                </div>
            </div>
        </article>

        <article class="hrm-modern-surface rounded-2xl p-5">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">My Team</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Recent colleagues in your directory.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($teamMates as $teamMate)
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <p class="font-semibold">{{ $teamMate->name }}</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $teamMate->profile?->job_title ?? 'Employee' }}</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $teamMate->email }}</p>
                    </li>
                @empty
                    <li class="rounded-xl border p-3 text-sm" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                        No teammates available yet.
                    </li>
                @endforelse
            </ul>
        </article>
    </section>
@endsection
