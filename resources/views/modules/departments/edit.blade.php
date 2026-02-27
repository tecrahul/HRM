@extends('layouts.dashboard-modern')

@section('title', 'Edit Department')
@section('page_heading', 'Edit Department')

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
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Update Department</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Modify department details and status.</p>
                </div>
            </div>
            <a href="{{ route('modules.departments.index') }}" class="rounded-xl px-3 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                Back
            </a>
        </div>

        <form method="POST" action="{{ route('modules.departments.update', $department) }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Department Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $department->name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Department Code</label>
                <input id="code" name="code" type="text" value="{{ old('code', $department->code) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('description', $department->description) }}</textarea>
                @error('description')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department->is_active ? '1' : '0') === '1' ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                    Active Department
                </label>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <button type="submit" class="ui-btn ui-btn-primary">
                    <x-heroicon-o-check class="h-4 w-4" />
                    Update Department
                </button>
                <a href="{{ route('modules.departments.index') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                    Cancel
                </a>
            </div>
        </form>
    </section>
@endsection
