@extends('layouts.dashboard-modern')

@section('title', 'Edit Holiday')
@section('page_heading', 'Edit Holiday')

@section('content')
    @if ($errors->any())
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">Please fix the highlighted fields and submit again.</p>
        </section>
    @endif

    <section class="hrm-modern-surface rounded-2xl p-5 max-w-3xl">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Update Holiday</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Modify holiday date, scope, and optional status.</p>
                </div>
            </div>
            <a href="{{ route('modules.holidays.index', ['fy' => $selectedFy]) }}" class="rounded-xl px-3 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                Back
            </a>
        </div>

        <form method="POST" action="{{ route('modules.holidays.update', $holiday) }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="fy" value="{{ $selectedFy }}">

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Holiday Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $holiday->name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="holiday_date" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Date</label>
                <input id="holiday_date" name="holiday_date" type="date" value="{{ old('holiday_date', $holiday->holiday_date?->format('Y-m-d')) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('holiday_date')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="branch_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Holiday Scope</label>
                @php
                    $selectedBranch = old('branch_id', $holiday->branch_id ? (string) $holiday->branch_id : '');
                @endphp
                <select id="branch_id" name="branch_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Company-wide</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $selectedBranch === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('description', $holiday->description) }}</textarea>
                @error('description')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_optional" value="1" {{ old('is_optional', $holiday->is_optional ? '1' : '0') === '1' ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                    Optional Holiday
                </label>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white inline-flex items-center gap-2" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                    Update Holiday
                </button>
                <a href="{{ route('modules.holidays.index', ['fy' => $selectedFy]) }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection
