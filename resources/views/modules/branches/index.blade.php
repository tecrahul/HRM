@extends('layouts.dashboard-modern')

@section('title', 'Branches')
@section('page_heading', 'Branch Management')

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
                    <h3 class="text-lg font-extrabold">Create Branch</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Only admin can create and maintain branches.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('modules.branches.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Branch Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="New York HQ">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Branch Code</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="NY-HQ">
                    @error('code')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Location</label>
                    <input id="location" name="location" type="text" value="{{ old('location') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="New York, USA">
                    @error('location')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);" placeholder="Primary corporate office.">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                    Active Branch
                </label>

                <button type="submit" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold text-white inline-flex items-center justify-center gap-2" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                    Create Branch
                </button>
            </form>
        </article>

        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"></path><path d="M6 20V8l6-4 6 4v12"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Branches</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Configured branch list for employee records.</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                    <tr class="border-b text-left" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
                        <th class="py-2.5 px-2 font-semibold">Name</th>
                        <th class="py-2.5 px-2 font-semibold">Code</th>
                        <th class="py-2.5 px-2 font-semibold">Location</th>
                        <th class="py-2.5 px-2 font-semibold">Status</th>
                        <th class="py-2.5 px-2 font-semibold">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($branches as $branch)
                        <tr class="border-b" style="border-color: var(--hr-line);">
                            <td class="py-2.5 px-2 font-semibold">{{ $branch->name }}</td>
                            <td class="py-2.5 px-2">{{ $branch->code ?: 'N/A' }}</td>
                            <td class="py-2.5 px-2">{{ $branch->location ?: 'N/A' }}</td>
                            <td class="py-2.5 px-2">
                                <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="{{ $branch->is_active ? 'color:#15803d;background:rgb(34 197 94 / 0.16);' : 'color:#b45309;background:rgb(245 158 11 / 0.18);' }}">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2">
                                <a href="{{ route('modules.branches.edit', $branch) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-sm" style="color: var(--hr-text-muted);">No branches created yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $branches->links() }}
            </div>
        </article>
    </section>
@endsection
