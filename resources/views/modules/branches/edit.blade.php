@extends('layouts.dashboard-modern')

@section('title', 'Edit Branch')
@section('page_heading', 'Edit Branch')

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
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"></path><path d="M6 20V8l6-4 6 4v12"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Update Branch</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Modify branch details and status.</p>
                </div>
            </div>
            <a href="{{ route('modules.branches.index') }}" class="rounded-xl px-3 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                Back
            </a>
        </div>

        <form method="POST" action="{{ route('modules.branches.update', $branch) }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Branch Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $branch->name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Branch Code</label>
                <input id="code" name="code" type="text" value="{{ old('code', $branch->code) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="location" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Location</label>
                <input id="location" name="location" type="text" value="{{ old('location', $branch->location) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('location')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('description', $branch->description) }}</textarea>
                @error('description')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active ? '1' : '0') === '1' ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                    Active Branch
                </label>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">
                    <x-heroicon-o-check class="h-4 w-4" />
                    Update Branch
                </button>
                <a href="{{ route('modules.branches.index') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection
