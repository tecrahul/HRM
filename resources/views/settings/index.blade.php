@extends('layouts.dashboard-modern')

@section('title', 'Settings')
@section('page_heading', 'System Settings')

@section('content')
    @php
        $canManageCompanyDetails = auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN);
        $companyLogoPath = (string) ($companySettings['company_logo_path'] ?? '');
        $companyLogoUrl = null;
        if (
            $companyLogoPath !== ''
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
        ) {
            $companyLogoUrl = route('settings.company.logo');
        }
    @endphp

    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please fix the highlighted company details fields and try again.</div>
    @endif

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Total Users</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $systemSnapshot['usersTotal'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Employees</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $systemSnapshot['employeesTotal'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Attendance Today</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $systemSnapshot['attendanceMarkedToday'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Pending Leaves</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $systemSnapshot['leavePending'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(245 158 11 / 0.16); color: #d97706;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <article class="ui-section">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect></svg>
                </span>
                <h3 class="text-lg font-extrabold">Module Snapshot</h3>
            </div>
            <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Live module records from the database.</p>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(236 72 153 / 0.16); color: #db2777;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                        </span>
                        <p class="font-semibold">Departments</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $systemSnapshot['departmentsTotal'] }} configured</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(99 102 241 / 0.16); color: #4f46e5;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"></path><path d="M6 20V8l6-4 6 4v12"></path></svg>
                        </span>
                        <p class="font-semibold">Branches</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $systemSnapshot['branchesTotal'] }} configured</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(124 58 237 / 0.16); color: #7c3aed;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                        </span>
                        <p class="font-semibold">Payroll (Month)</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $systemSnapshot['payrollGeneratedMonth'] }} generated</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg>
                        </span>
                        <p class="font-semibold">Company Profile</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $canManageCompanyDetails ? 'Editable' : 'Read only' }}</p>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82"></path><path d="M4.6 9a1.65 1.65 0 0 0-.33-1.82"></path></svg>
                </span>
                <h3 class="text-lg font-extrabold">Platform Information</h3>
            </div>
            <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Application metadata from runtime configuration.</p>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Application</dt>
                    <dd class="font-semibold">{{ $appMeta['appName'] ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">URL</dt>
                    <dd class="font-semibold">{{ $appMeta['appUrl'] ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Timezone</dt>
                    <dd class="font-semibold">{{ $appMeta['appTimezone'] ?: 'N/A' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Laravel</dt>
                    <dd class="font-semibold">{{ $appMeta['laravelVersion'] }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">PHP</dt>
                    <dd class="font-semibold">{{ $appMeta['phpVersion'] }}</dd>
                </div>
            </dl>
        </article>
    </section>

    <section class="ui-section">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-start gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center mt-0.5" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Company Details</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Configure organization profile data used across HR records, payroll, and reports.</p>
                </div>
            </div>
            <span class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent); border: 1px solid var(--hr-line);">
                {{ $canManageCompanyDetails ? 'Admin' : 'Read Only' }}
            </span>
        </div>

        @unless($canManageCompanyDetails)
            <p class="text-sm mt-3 text-amber-600">Only admin users can update company details.</p>
        @endunless

        <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label for="company_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Company Name</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $companySettings['company_name']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="h-20 w-20 rounded-xl border p-2 flex items-center justify-center overflow-hidden" style="border-color: var(--hr-line); background: #fff;">
                        @if ($companyLogoUrl)
                            <img src="{{ $companyLogoUrl }}" alt="Company logo" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-xs font-semibold text-center leading-tight" style="color: var(--hr-text-muted);">No Logo</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-[220px]">
                        <label for="company_logo" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Company Logo</label>
                        <input id="company_logo" name="company_logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        <p class="text-xs mt-2" style="color: var(--hr-text-muted);">Recommended: square logo, PNG/SVG/WebP, max 2MB.</p>
                        @if ($companyLogoUrl && $canManageCompanyDetails)
                            <label class="mt-2 inline-flex items-center gap-2 text-xs" style="color: var(--hr-text-muted);">
                                <input type="checkbox" name="remove_company_logo" value="1" class="rounded border-gray-300">
                                Remove current logo
                            </label>
                        @endif
                        @error('company_logo')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div>
                <label for="company_code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Company Code</label>
                <input id="company_code" name="company_code" type="text" value="{{ old('company_code', $companySettings['company_code']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_code')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="company_email" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Official Email</label>
                <input id="company_email" name="company_email" type="email" value="{{ old('company_email', $companySettings['company_email']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="company_phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Phone Number</label>
                <input id="company_phone" name="company_phone" type="text" value="{{ old('company_phone', $companySettings['company_phone']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_phone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="company_website" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Website</label>
                <input id="company_website" name="company_website" type="url" value="{{ old('company_website', $companySettings['company_website']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_website')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="tax_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Tax ID / EIN</label>
                <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id', $companySettings['tax_id']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('tax_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="timezone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Timezone</label>
                <select id="timezone" name="timezone" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $currentTimezone = old('timezone', $companySettings['timezone']);
                    @endphp
                    <option value="America/New_York" {{ $currentTimezone === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                    <option value="America/Chicago" {{ $currentTimezone === 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                    <option value="America/Los_Angeles" {{ $currentTimezone === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                </select>
                @error('timezone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="currency" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Base Currency</label>
                <select id="currency" name="currency" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $currentCurrency = old('currency', $companySettings['currency']);
                    @endphp
                    <option value="USD" {{ $currentCurrency === 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="EUR" {{ $currentCurrency === 'EUR' ? 'selected' : '' }}>EUR</option>
                    <option value="GBP" {{ $currentCurrency === 'GBP' ? 'selected' : '' }}>GBP</option>
                    <option value="INR" {{ $currentCurrency === 'INR' ? 'selected' : '' }}>INR</option>
                </select>
                @error('currency')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="financial_year_start_month" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Financial Year Start Month</label>
                @php
                    $currentFinancialYearStartMonth = (int) old('financial_year_start_month', $companySettings['financial_year_start_month'] ?? 4);
                @endphp
                <select id="financial_year_start_month" name="financial_year_start_month" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($financialYearMonthOptions as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" {{ $currentFinancialYearStartMonth === (int) $monthNumber ? 'selected' : '' }}>
                            {{ $monthLabel }}
                        </option>
                    @endforeach
                </select>
                @error('financial_year_start_month')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label for="company_address" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Head Office Address</label>
                <textarea id="company_address" name="company_address" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('company_address', $companySettings['company_address']) }}</textarea>
                @error('company_address')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <h4 class="text-sm font-extrabold">Authentication Access</h4>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Control whether users can sign up and reset passwords from the login screen.</p>
                <div class="mt-3 space-y-3">
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="checkbox"
                            name="signup_enabled"
                            value="1"
                            class="mt-0.5 rounded border-gray-300"
                            @checked((bool) old('signup_enabled', $companySettings['signup_enabled']))
                            @disabled(! $canManageCompanyDetails)
                        >
                        <span>
                            <span class="font-semibold block">Enable Sign Up</span>
                            <span class="text-xs" style="color: var(--hr-text-muted);">Allow new users to create employee accounts from the public auth page.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="checkbox"
                            name="password_reset_enabled"
                            value="1"
                            class="mt-0.5 rounded border-gray-300"
                            @checked((bool) old('password_reset_enabled', $companySettings['password_reset_enabled']))
                            @disabled(! $canManageCompanyDetails)
                        >
                        <span>
                            <span class="font-semibold block">Enable Password Reset</span>
                            <span class="text-xs" style="color: var(--hr-text-muted);">Allow users to request email reset links and set a new password.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="md:col-span-2 mt-1 flex flex-wrap items-center gap-2">
                @if ($canManageCompanyDetails)
                    <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">Save Company Details</button>
                @endif
                <button type="reset" class="rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">Reset</button>
                <span class="text-xs" style="color: var(--hr-text-muted);">Saved values are loaded from the database.</span>
            </div>
        </form>
    </section>
@endsection
