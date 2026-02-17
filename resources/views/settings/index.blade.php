@extends('layouts.dashboard-modern')

@section('title', 'Settings')
@section('page_heading', 'System Settings')

@section('content')
    @php
        $canManageCompanyDetails = auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN);
        $companyLogoPath = (string) ($companySettings['company_logo_path'] ?? '');
        $companyLogoUrl = null;
        $profileFieldsForCompletion = [
            'company_name',
            'company_code',
            'company_email',
            'company_phone',
            'company_website',
            'tax_id',
            'company_address',
        ];
        $completedProfileFields = collect($profileFieldsForCompletion)->filter(
            static fn (string $field): bool => filled($companySettings[$field] ?? null)
        )->count();
        $profileCompletionPercent = (int) round(
            ($completedProfileFields / count($profileFieldsForCompletion)) * 100
        );
        $authControlsEnabled = collect(['signup_enabled', 'password_reset_enabled', 'two_factor_enabled'])->filter(
            static fn (string $field): bool => (bool) ($companySettings[$field] ?? false)
        )->count();
        $currentFinancialMonth = (int) ($companySettings['financial_year_start_month'] ?? 4);
        $financialYearStartLabel = $financialYearMonthOptions[$currentFinancialMonth] ?? 'April';
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
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Profile Completion</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $profileCompletionPercent }}%</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $completedProfileFields }}/{{ count($profileFieldsForCompletion) }} fields configured</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Branding</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $companyLogoUrl ? 'Configured' : 'Not Set' }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">Company logo for login and dashboards</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Auth Controls</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $authControlsEnabled }}/3 Enabled</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">Sign up, password reset, and 2FA</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Locale & Finance</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $companySettings['currency'] }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $companySettings['timezone'] }} - FY starts {{ $financialYearStartLabel }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(245 158 11 / 0.16); color: #d97706;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"></path><path d="M12 6v12"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <article class="ui-section">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
                <h3 class="text-lg font-extrabold">Company Settings Snapshot</h3>
            </div>
            <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Current values configured for your organization profile.</p>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(236 72 153 / 0.16); color: #db2777;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                        </span>
                        <p class="font-semibold">Company Name</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $companySettings['company_name'] ?: 'Not set' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(99 102 241 / 0.16); color: #4f46e5;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18"></path><path d="M3 12h18"></path><path d="M3 19h18"></path></svg>
                        </span>
                        <p class="font-semibold">Company Code</p>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $companySettings['company_code'] ?: 'Not set' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(124 58 237 / 0.16); color: #7c3aed;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"></path><path d="m22 6-10 7L2 6"></path></svg>
                        </span>
                        <p class="font-semibold">Official Email</p>
                    </div>
                    <p class="mt-1 text-xs break-all" style="color: var(--hr-text-muted);">{{ $companySettings['company_email'] ?: 'Not set' }}</p>
                </div>
                <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="flex items-center gap-2">
                        <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3.46 3.46"></path><path d="M17 10a5 5 0 1 0-10 0 5 5 0 0 0 10 0z"></path></svg>
                        </span>
                        <p class="font-semibold">Website</p>
                    </div>
                    <p class="mt-1 text-xs break-all" style="color: var(--hr-text-muted);">{{ $companySettings['company_website'] ?: 'Not set' }}</p>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </span>
                <h3 class="text-lg font-extrabold">Access & Preferences</h3>
            </div>
            <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Authentication and regional preferences from the saved settings.</p>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Sign Up</dt>
                    <dd class="font-semibold">{{ $companySettings['signup_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Password Reset</dt>
                    <dd class="font-semibold">{{ $companySettings['password_reset_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Two-Factor Authentication</dt>
                    <dd class="font-semibold">{{ $companySettings['two_factor_enabled'] ? 'Enabled' : 'Disabled' }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Timezone</dt>
                    <dd class="font-semibold">{{ $companySettings['timezone'] }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Currency</dt>
                    <dd class="font-semibold">{{ $companySettings['currency'] }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Financial Year Start</dt>
                    <dd class="font-semibold">{{ $financialYearStartLabel }}</dd>
                </div>
                <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                    <dt style="color: var(--hr-text-muted);">Edit Access</dt>
                    <dd class="font-semibold">{{ $canManageCompanyDetails ? 'Admin' : 'Read only' }}</dd>
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
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-extrabold">Security Controls</h4>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Two-factor authentication can be globally enabled or disabled from Authentication Access.</p>
                    </div>
                    <span class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent); border: 1px solid var(--hr-line);">
                        2FA: {{ ($companySettings['two_factor_enabled'] ?? true) ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <div class="mt-3">
                    <a href="#authentication-access" class="text-xs font-semibold inline-flex items-center gap-2" style="color: var(--hr-accent);">
                        Go to Authentication Access
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
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
            <div id="authentication-access" class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <h4 class="text-sm font-extrabold">Authentication Access</h4>
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Control whether users can sign up, reset passwords, and use two-factor authentication.</p>
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
                    <label class="flex items-start gap-3 text-sm">
                        <input
                            type="checkbox"
                            name="two_factor_enabled"
                            value="1"
                            class="mt-0.5 rounded border-gray-300"
                            @checked((bool) old('two_factor_enabled', $companySettings['two_factor_enabled'] ?? true))
                            @disabled(! $canManageCompanyDetails)
                        >
                        <span>
                            <span class="font-semibold block">Enable Two-Factor Authentication</span>
                            <span class="text-xs" style="color: var(--hr-text-muted);">Allow users to enable and use TOTP-based two-factor authentication at login.</span>
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
