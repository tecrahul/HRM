@extends('layouts.dashboard-modern')

@section('title', 'Departments')
@section('page_heading', 'Department Management')

@section('content')
    @if (session('status'))
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-emerald-600">{{ session('status') }}</p>
        </section>
    @endif

    @if ($errors->any())
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">Please fix the highlighted fields and submit again.</p>
        </section>
    @endif

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-1">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Create Department</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Add a department for employee assignment and HR workflows.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.departments.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Department Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="Engineering">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Department Code</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="ENG">
                    @error('code')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);" placeholder="Department purpose and responsibilities">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                    Active Department
                </label>

                <button type="submit" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold text-white inline-flex items-center justify-center gap-2" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                    Create Department
                </button>
            </form>
        </article>

        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold">Departments</h3>
                        <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Available departments used across employee and user forms.</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead>
                    <tr class="border-b text-left" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
                        <th class="py-2.5 px-2 font-semibold">Name</th>
                        <th class="py-2.5 px-2 font-semibold">Code</th>
                        <th class="py-2.5 px-2 font-semibold">Description</th>
                        <th class="py-2.5 px-2 font-semibold">Status</th>
                        <th class="py-2.5 px-2 font-semibold">Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($departments as $department)
                        <tr class="border-b" style="border-color: var(--hr-line);">
                            <td class="py-2.5 px-2 font-semibold">{{ $department->name }}</td>
                            <td class="py-2.5 px-2">{{ $department->code ?: 'N/A' }}</td>
                            <td class="py-2.5 px-2">{{ $department->description ?: 'N/A' }}</td>
                            <td class="py-2.5 px-2">
                                <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="{{ $department->is_active ? 'color:#15803d;background:rgb(34 197 94 / 0.16);' : 'color:#b45309;background:rgb(245 158 11 / 0.18);' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2">{{ $department->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-sm" style="color: var(--hr-text-muted);">No departments created yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $departments->links() }}
            </div>
        </article>
    </section>
@endsection
