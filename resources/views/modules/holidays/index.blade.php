@extends('layouts.dashboard-modern')

@section('title', 'Holidays')
@section('page_heading', 'Holiday Calendar')

@section('content')
    @if (session('status'))
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-emerald-600">{{ session('status') }}</p>
        </section>
    @endif

    @if ($errors->any())
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">Please fix the highlighted holiday fields and submit again.</p>
        </section>
    @endif

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Financial Year</p>
            <p class="mt-2 text-2xl font-extrabold">{{ $fyOptions[$selectedFy] ?? ('FY ' . $selectedFy . '-' . ($selectedFy + 1)) }}</p>
            <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $rangeStart->format('M d, Y') }} - {{ $rangeEnd->format('M d, Y') }}</p>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">FY Start Month</p>
            <p class="mt-2 text-2xl font-extrabold">{{ $financialYearStartMonthLabel }}</p>
            @if ($canManageHolidays)
                <a href="{{ route('settings.index') }}" class="text-xs mt-1 inline-flex" style="color: var(--hr-accent);">Update in Settings</a>
            @endif
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Total Holidays</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total'] }}</p>
            <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Company + branch holidays</p>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Optional Holidays</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['optional'] }}</p>
            <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Optional / floating holidays</p>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        @if ($canManageHolidays)
            <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-1">
                <div class="flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold">Create Holiday</h3>
                        <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Add a company-wide or branch-specific holiday.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('modules.holidays.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="fy" value="{{ $selectedFy }}">

                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Holiday Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="New Year">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="holiday_date" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Date</label>
                        <input id="holiday_date" name="holiday_date" type="date" value="{{ old('holiday_date') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        @error('holiday_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="branch_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Holiday Scope</label>
                        <select id="branch_id" name="branch_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                            <option value="">Company-wide</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Description</label>
                        <textarea id="description" name="description" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);" placeholder="Holiday notes or restrictions.">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="is_optional" value="1" {{ old('is_optional') ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                        Optional Holiday
                    </label>

                    <button type="submit" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold text-white inline-flex items-center justify-center gap-2" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                        Create Holiday
                    </button>
                </form>
            </article>
        @endif

        <article class="hrm-modern-surface rounded-2xl p-5 {{ $canManageHolidays ? 'xl:col-span-2' : 'xl:col-span-3' }}">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold">Holiday Calendar</h3>
                        <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Financial-year holidays used by leave and payroll calculations.</p>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('modules.holidays.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="branch_id" class="rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">All Scopes</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $filters['branch_id'] === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <select name="fy" class="flex-1 rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        @foreach($fyOptions as $fyStartYear => $fyLabel)
                            <option value="{{ $fyStartYear }}" {{ $selectedFy === (int) $fyStartYear ? 'selected' : '' }}>{{ $fyLabel }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xl px-3 py-2.5 text-sm font-semibold border" style="border-color: var(--hr-line);">Filter</button>
                </div>
            </form>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[840px] text-sm">
                    <thead>
                    <tr class="border-b text-left" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
                        <th class="py-2.5 px-2 font-semibold">Date</th>
                        <th class="py-2.5 px-2 font-semibold">Holiday</th>
                        <th class="py-2.5 px-2 font-semibold">Scope</th>
                        <th class="py-2.5 px-2 font-semibold">Type</th>
                        <th class="py-2.5 px-2 font-semibold">Description</th>
                        @if ($canManageHolidays)
                            <th class="py-2.5 px-2 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($holidays as $holiday)
                        <tr class="border-b" style="border-color: var(--hr-line);">
                            <td class="py-2.5 px-2 font-semibold">{{ $holiday->holiday_date?->format('M d, Y') }}</td>
                            <td class="py-2.5 px-2">{{ $holiday->name }}</td>
                            <td class="py-2.5 px-2">{{ $holiday->branch?->name ?? 'Company-wide' }}</td>
                            <td class="py-2.5 px-2">
                                <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="{{ $holiday->is_optional ? 'color:#b45309;background:rgb(245 158 11 / 0.18);' : 'color:#15803d;background:rgb(34 197 94 / 0.16);' }}">
                                    {{ $holiday->is_optional ? 'Optional' : 'Mandatory' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2">{{ $holiday->description ?: 'N/A' }}</td>
                            @if ($canManageHolidays)
                                <td class="py-2.5 px-2">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('modules.holidays.edit', ['holiday' => $holiday, 'fy' => $selectedFy]) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">Edit</a>
                                        <form method="POST" action="{{ route('modules.holidays.destroy', $holiday) }}" onsubmit="return confirm('Delete this holiday?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="fy" value="{{ $selectedFy }}">
                                            <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600" style="border-color: rgb(239 68 68 / 0.45);">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageHolidays ? 6 : 5 }}" class="py-6 text-center text-sm" style="color: var(--hr-text-muted);">No holidays found for the selected financial year.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $holidays->links() }}
            </div>
        </article>
    </section>
@endsection
