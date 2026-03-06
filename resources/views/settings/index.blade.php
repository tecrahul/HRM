@extends('layouts.dashboard-modern')

@section('title', 'Settings')
@section('page_heading', $settingsPageHeading ?? 'Settings')

@section('content')
    @php
        $canManageCompanyDetails = auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN->value);
        $settingsSection = in_array(($settingsSection ?? 'overview'), ['overview', 'company', 'system'], true)
            ? $settingsSection
            : 'overview';
        $isOverviewSection = $settingsSection === 'overview';
        $isCompanySection = $settingsSection === 'company';
        $isSystemSection = $settingsSection === 'system';
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
            'legal_entity_name',
            'registration_number',
            'incorporation_country',
            'brand_tagline',
        ];
        $completedProfileFields = collect($profileFieldsForCompletion)->filter(
            static fn (string $field): bool => filled($companySettings[$field] ?? null)
        )->count();
        $profileCompletionPercent = (int) round(
            ($completedProfileFields / count($profileFieldsForCompletion)) * 100
        );
        $currentFinancialMonth = (int) ($companySettings['financial_year_start_month'] ?? 4);
        $financialYearStartDay = (int) ($companySettings['financial_year_start_day'] ?? 1);
        $financialYearStartLabel = \Carbon\Carbon::create(2024, $currentFinancialMonth, $financialYearStartDay)->format('F j');
        $financialYearEndLabel = \Carbon\Carbon::create(
            2024,
            (int) ($companySettings['financial_year_end_month'] ?? 3),
            (int) ($companySettings['financial_year_end_day'] ?? 31)
        )->format('F j');
        $selectedLocaleLabel = $localeOptions[$companySettings['locale']] ?? $companySettings['locale'];
        $selectedDefaultCountryLabel = $countryOptions[$companySettings['default_country']] ?? $companySettings['default_country'];
        $selectedEntityTypeLabel = $legalEntityTypes[$companySettings['legal_entity_type'] ?? ''] ?? 'Not set';
        if (
            $companyLogoPath !== ''
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogoPath)
        ) {
            $companyLogoUrl = route('settings.company.logo');
        }

        // Branch directory helpers for the Company section
        $branchDirectoryEntries = old('branch_directory', $companySettings['branch_directory'] ?? []);
        if (! is_array($branchDirectoryEntries)) {
            $branchDirectoryEntries = [];
        }
        $branchDirectoryCount = count($branchDirectoryEntries);
    @endphp

    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please fix the highlighted fields and try again.</div>
    @endif

    @if ($isOverviewSection)
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
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Legal Entity</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $companySettings['legal_entity_name'] ?: 'Not Set' }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">
                        {{ $selectedEntityTypeLabel }}
                        @if (filled($companySettings['registration_number']))
                            • Reg {{ $companySettings['registration_number'] }}
                        @endif
                    </p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Localization & Coverage</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $companySettings['currency'] }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $selectedLocaleLabel }} • TZ {{ $companySettings['timezone'] }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">FY {{ $financialYearStartLabel }} → {{ $financialYearEndLabel }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(245 158 11 / 0.16); color: #d97706;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"></path><path d="M12 6v12"></path></svg>
                </span>
            </div>
        </article>
        </section>
    @endif

    @if ($isOverviewSection)
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <article class="ui-section">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                        </span>
                        <h3 class="text-lg font-extrabold">Company Settings Snapshot</h3>
                    </div>
                    <a href="{{ route('settings.index', ['section' => 'company']) }}" class="inline-flex items-center gap-2 text-sm font-semibold" style="color: var(--hr-accent);">
                        Edit Company Settings
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                    </a>
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
                            <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(45 212 191 / 0.16); color: #0f766e;">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                            </span>
                            <p class="font-semibold">Legal Entity Name</p>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $companySettings['legal_entity_name'] ?: 'Not set' }}</p>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(249 115 22 / 0.16); color: #c2410c;">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"></path><path d="M9 4v16"></path><path d="M4 9h16"></path></svg>
                            </span>
                            <p class="font-semibold">Legal Entity Type</p>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $selectedEntityTypeLabel }}</p>
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
                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(248 113 113 / 0.16); color: #dc2626;">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20v-6"></path><path d="M12 4v2"></path><path d="M6 12h12"></path></svg>
                            </span>
                            <p class="font-semibold">Registration #</p>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $companySettings['registration_number'] ?: 'Not set' }}</p>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(6 182 212 / 0.16); color: #0e7490;">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </span>
                            <p class="font-semibold">Default Country</p>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $selectedDefaultCountryLabel }}</p>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 rounded-lg flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18"></path><path d="M3 12h18"></path><path d="M3 19h18"></path></svg>
                            </span>
                            <p class="font-semibold">Locale</p>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">{{ $selectedLocaleLabel }}</p>
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
                        <dt style="color: var(--hr-text-muted);">Locale</dt>
                        <dd class="font-semibold">{{ $selectedLocaleLabel }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Date Format</dt>
                        <dd class="font-semibold">{{ $dateFormatOptions[$companySettings['date_format']] ?? $companySettings['date_format'] }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Time Format</dt>
                        <dd class="font-semibold">{{ $timeFormatOptions[$companySettings['time_format']] ?? $companySettings['time_format'] }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Currency</dt>
                        <dd class="font-semibold">{{ $companySettings['currency'] }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Financial Year</dt>
                        <dd class="font-semibold">{{ $financialYearStartLabel }} → {{ $financialYearEndLabel }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Default Country</dt>
                        <dd class="font-semibold">{{ $selectedDefaultCountryLabel }}</dd>
                    </div>
                    <div class="rounded-xl border px-3 py-2.5 flex items-center justify-between gap-2" style="border-color: var(--hr-line);">
                        <dt style="color: var(--hr-text-muted);">Edit Access</dt>
                        <dd class="font-semibold">{{ $canManageCompanyDetails ? 'Admin' : 'Read only' }}</dd>
                    </div>
                </dl>
            </article>
            <article class="ui-section">
                <div class="flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </span>
                    <h3 class="text-lg font-extrabold">System Settings</h3>
                </div>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Configure authentication controls, locale, currency, and financial year preferences.</p>
                <a href="{{ route('settings.index', ['section' => 'system']) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold" style="color: var(--hr-accent);">
                    Open System Settings
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                </a>
            </article>
        </section>
    @endif

    @if (! $isOverviewSection)
        <section class="ui-section">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-start gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center mt-0.5" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">{{ $isSystemSection ? 'System Preferences' : 'Company Details' }}</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">
                        {{ $isSystemSection ? 'Control authentication, localization, and financial year defaults used across the platform.' : 'Configure organization profile data used across HR records, payroll, and reports.' }}
                    </p>
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
            <input type="hidden" name="settings_section" value="{{ $isSystemSection ? 'system' : 'company' }}">
            @if ($isCompanySection)
            <div>
                <label for="company_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Company Name</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $companySettings['company_name']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('company_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="legal_entity_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Legal Entity Name</label>
                <input id="legal_entity_name" name="legal_entity_name" type="text" value="{{ old('legal_entity_name', $companySettings['legal_entity_name']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('legal_entity_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="legal_entity_type" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Legal Entity Type</label>
                <select id="legal_entity_type" name="legal_entity_type" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select type</option>
                    @foreach($legalEntityTypes as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}" @selected(old('legal_entity_type', $companySettings['legal_entity_type']) === $typeValue)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
                @error('legal_entity_type')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="registration_number" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Registration Number</label>
                <input id="registration_number" name="registration_number" type="text" value="{{ old('registration_number', $companySettings['registration_number']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('registration_number')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="incorporation_country" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Incorporation Country</label>
                @php
                    $selectedIncorporationCountry = old('incorporation_country', $companySettings['incorporation_country']);
                @endphp
                <select id="incorporation_country" name="incorporation_country" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select country</option>
                    @foreach($countryOptions as $countryCode => $countryLabel)
                        <option value="{{ $countryCode }}" @selected($selectedIncorporationCountry === $countryCode)>{{ $countryLabel }}</option>
                    @endforeach
                </select>
                @error('incorporation_country')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="brand_tagline" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Brand Tagline</label>
                <input id="brand_tagline" name="brand_tagline" type="text" value="{{ old('brand_tagline', $companySettings['brand_tagline']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('brand_tagline')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex items-center gap-2 mb-3">
                    <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: rgb(99 102 241 / 0.16); color: #4f46e5;">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </span>
                    <div>
                        <h4 class="text-sm font-extrabold">Company Logo</h4>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Upload and crop your company logo for branding.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-start gap-4">
                    {{-- Current Logo Preview --}}
                    <div class="shrink-0">
                        <div id="logo-preview-container" class="h-24 w-24 rounded-xl border-2 border-dashed p-2 flex items-center justify-center overflow-hidden transition-all" style="border-color: var(--hr-line); background: #fff;">
                            @if ($companyLogoUrl)
                                <img id="logo-preview-image" src="{{ $companyLogoUrl }}" alt="Company logo" class="max-h-full max-w-full object-contain">
                            @else
                                <div id="logo-placeholder" class="text-center">
                                    <svg class="h-8 w-8 mx-auto" style="color: var(--hr-text-muted);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                    <span class="text-[10px] font-semibold block mt-1" style="color: var(--hr-text-muted);">No Logo</span>
                                </div>
                            @endif
                        </div>
                        <p class="text-[10px] mt-1.5 text-center" style="color: var(--hr-text-muted);">Preview</p>
                    </div>

                    {{-- Upload Controls --}}
                    <div class="flex-1 min-w-[240px]">
                        <input type="hidden" id="cropped_logo_data" name="cropped_logo_data" value="">
                        <input type="file" id="company_logo_input" accept="image/jpeg,image/png,image/webp" class="hidden">

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="select-logo-btn" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold border transition-colors" style="border-color: var(--hr-line); background: var(--hr-surface);" @disabled(!$canManageCompanyDetails)>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                {{ $companyLogoUrl ? 'Change Logo' : 'Upload Logo' }}
                            </button>
                            @if ($companyLogoUrl && $canManageCompanyDetails)
                                <label class="inline-flex items-center gap-2 text-sm cursor-pointer" style="color: var(--hr-text-muted);">
                                    <input type="checkbox" id="remove_logo_checkbox" name="remove_company_logo" value="1" class="rounded border-gray-300">
                                    Remove logo
                                </label>
                            @endif
                        </div>

                        <p class="text-xs mt-3" style="color: var(--hr-text-muted);">
                            <strong>Supported formats:</strong> JPG, PNG, WebP<br>
                            <strong>Recommended:</strong> Square image, at least 200x200px<br>
                            <strong>Max size:</strong> 2MB
                        </p>
                        @error('company_logo')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                        @error('cropped_logo_data')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Logo Crop Modal --}}
            <div id="logo-crop-modal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true" aria-labelledby="crop-modal-title">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" id="crop-modal-backdrop"></div>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div class="relative w-full max-w-lg rounded-2xl shadow-2xl" style="background: var(--hr-surface);">
                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b" style="border-color: var(--hr-line);">
                            <div class="flex items-center gap-3">
                                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(99 102 241 / 0.16); color: #4f46e5;">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                </span>
                                <div>
                                    <h3 id="crop-modal-title" class="text-base font-bold" style="color: var(--hr-text-main);">Crop Logo</h3>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">Adjust the crop area to fit your logo</p>
                                </div>
                            </div>
                            <button type="button" id="crop-modal-close" class="h-8 w-8 rounded-lg flex items-center justify-center transition-colors" style="background: var(--hr-surface-strong);" aria-label="Close">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Crop Area --}}
                        <div class="p-5">
                            <div id="crop-container" class="relative w-full overflow-hidden rounded-xl" style="height: 300px; background: #1a1a2e;">
                                <img id="crop-image" src="" alt="Crop preview" class="max-w-full" style="display: block;">
                            </div>

                            {{-- Crop Controls --}}
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" id="crop-zoom-out" class="h-9 w-9 rounded-lg flex items-center justify-center border" style="border-color: var(--hr-line);" title="Zoom out">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                    </button>
                                    <button type="button" id="crop-zoom-in" class="h-9 w-9 rounded-lg flex items-center justify-center border" style="border-color: var(--hr-line);" title="Zoom in">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                    </button>
                                    <button type="button" id="crop-rotate" class="h-9 w-9 rounded-lg flex items-center justify-center border" style="border-color: var(--hr-line);" title="Rotate 90°">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/></svg>
                                    </button>
                                    <button type="button" id="crop-reset" class="h-9 w-9 rounded-lg flex items-center justify-center border" style="border-color: var(--hr-line);" title="Reset">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </div>
                                <span class="text-xs" style="color: var(--hr-text-muted);">Drag to reposition</span>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t" style="border-color: var(--hr-line);">
                            <button type="button" id="crop-cancel-btn" class="rounded-xl px-4 py-2.5 text-sm font-semibold border" style="border-color: var(--hr-line);">
                                Cancel
                            </button>
                            <button type="button" id="crop-apply-btn" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                                Apply Crop
                            </button>
                        </div>
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
            @endif

            @if ($isSystemSection)
            <div>
                <label for="timezone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Timezone</label>
                <select id="timezone" name="timezone" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $currentTimezone = old('timezone', $companySettings['timezone']);
                    @endphp
                    @foreach($timezoneOptions as $timezoneValue => $timezoneLabel)
                        <option value="{{ $timezoneValue }}" {{ $currentTimezone === $timezoneValue ? 'selected' : '' }}>{{ $timezoneLabel }}</option>
                    @endforeach
                </select>
                @error('timezone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="locale" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Locale</label>
                @php
                    $currentLocale = old('locale', $companySettings['locale']);
                @endphp
                <select id="locale" name="locale" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($localeOptions as $localeValue => $localeLabel)
                        <option value="{{ $localeValue }}" {{ $currentLocale === $localeValue ? 'selected' : '' }}>{{ $localeLabel }}</option>
                    @endforeach
                </select>
                @error('locale')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="default_country" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Default Country</label>
                @php
                    $currentDefaultCountry = old('default_country', $companySettings['default_country']);
                @endphp
                <select id="default_country" name="default_country" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($countryOptions as $countryCode => $countryLabel)
                        <option value="{{ $countryCode }}" {{ $currentDefaultCountry === $countryCode ? 'selected' : '' }}>{{ $countryLabel }}</option>
                    @endforeach
                </select>
                @error('default_country')
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
                <label for="date_format" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Date Format</label>
                @php
                    $currentDateFormat = old('date_format', $companySettings['date_format']);
                @endphp
                <select id="date_format" name="date_format" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($dateFormatOptions as $formatValue => $formatPreview)
                        <option value="{{ $formatValue }}" {{ $currentDateFormat === $formatValue ? 'selected' : '' }}>{{ $formatPreview }} ({{ $formatValue }})</option>
                    @endforeach
                </select>
                @error('date_format')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="time_format" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Time Format</label>
                @php
                    $currentTimeFormat = old('time_format', $companySettings['time_format']);
                @endphp
                <select id="time_format" name="time_format" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($timeFormatOptions as $formatValue => $formatPreview)
                        <option value="{{ $formatValue }}" {{ $currentTimeFormat === $formatValue ? 'selected' : '' }}>{{ $formatPreview }} ({{ $formatValue }})</option>
                    @endforeach
                </select>
                @error('time_format')
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
            <div>
                <label for="financial_year_start_day" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Financial Year Start Day</label>
                @php
                    $currentFinancialYearStartDay = (int) old('financial_year_start_day', $companySettings['financial_year_start_day'] ?? 1);
                @endphp
                <select id="financial_year_start_day" name="financial_year_start_day" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($financialYearDayOptions as $dayOption)
                        <option value="{{ $dayOption }}" {{ $currentFinancialYearStartDay === (int) $dayOption ? 'selected' : '' }}>{{ $dayOption }}</option>
                    @endforeach
                </select>
                @error('financial_year_start_day')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="financial_year_end_month" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Financial Year End Month</label>
                @php
                    $currentFinancialYearEndMonth = (int) old('financial_year_end_month', $companySettings['financial_year_end_month'] ?? 3);
                @endphp
                <select id="financial_year_end_month" name="financial_year_end_month" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($financialYearMonthOptions as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" {{ $currentFinancialYearEndMonth === (int) $monthNumber ? 'selected' : '' }}>
                            {{ $monthLabel }}
                        </option>
                    @endforeach
                </select>
                @error('financial_year_end_month')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="financial_year_end_day" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Financial Year End Day</label>
                @php
                    $currentFinancialYearEndDay = (int) old('financial_year_end_day', $companySettings['financial_year_end_day'] ?? 31);
                @endphp
                <select id="financial_year_end_day" name="financial_year_end_day" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @foreach($financialYearDayOptions as $dayOption)
                        <option value="{{ $dayOption }}" {{ $currentFinancialYearEndDay === (int) $dayOption ? 'selected' : '' }}>{{ $dayOption }}</option>
                    @endforeach
                </select>
                @error('financial_year_end_day')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            @if ($isCompanySection)
            <div class="md:col-span-2">
                <label for="company_address" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Head Office Address</label>
                <textarea id="company_address" name="company_address" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('company_address', $companySettings['company_address']) }}</textarea>
                @error('company_address')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            @if ($isSystemSection)
            <div id="security" class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <h4 class="text-sm font-extrabold">Access & Security</h4>
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
            @endif

            <div class="md:col-span-2 mt-1 flex flex-wrap items-center gap-2">
                @if ($canManageCompanyDetails)
                    <button type="submit" class="ui-btn ui-btn-primary">{{ $isSystemSection ? 'Save System Settings' : 'Save Company Details' }}</button>
                @endif
                <button type="reset" class="rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">Reset</button>
                <span class="text-xs" style="color: var(--hr-text-muted);">Saved values are loaded from the database.</span>
            </div>
        </form>
    </section>
    @endif

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" integrity="sha512-hvNR0F/e2J7zPPfLC9auFe3/SE0yG4aJCOd/qxew74NN7eyiSKjr7xJJMu1Jy2wf7FXITpWS1E/RY8yzuXN7VA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    #crop-container .cropper-view-box,
    #crop-container .cropper-face {
        border-radius: 0.5rem;
    }
    #crop-container .cropper-view-box {
        outline: 2px solid #4f46e5;
        outline-offset: -2px;
    }
    #crop-container .cropper-line {
        background-color: #4f46e5;
    }
    #crop-container .cropper-point {
        background-color: #4f46e5;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    #crop-container .cropper-modal {
        background-color: rgba(0, 0, 0, 0.7);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js" integrity="sha512-9KkIqdfN7ipEW6B6k+Aq20PV31bjODg4AA52W+tYtAE0jE0kMx49bjJ3FgvS56wzmyfMUHbQ4Km2b7l9+Y/+Eg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function() {
    'use strict';

    const selectBtn = document.getElementById('select-logo-btn');
    const fileInput = document.getElementById('company_logo_input');
    const croppedDataInput = document.getElementById('cropped_logo_data');
    const previewContainer = document.getElementById('logo-preview-container');
    const removeCheckbox = document.getElementById('remove_logo_checkbox');

    const modal = document.getElementById('logo-crop-modal');
    const modalBackdrop = document.getElementById('crop-modal-backdrop');
    const closeBtn = document.getElementById('crop-modal-close');
    const cancelBtn = document.getElementById('crop-cancel-btn');
    const applyBtn = document.getElementById('crop-apply-btn');
    const cropImage = document.getElementById('crop-image');

    const zoomInBtn = document.getElementById('crop-zoom-in');
    const zoomOutBtn = document.getElementById('crop-zoom-out');
    const rotateBtn = document.getElementById('crop-rotate');
    const resetBtn = document.getElementById('crop-reset');

    let cropper = null;
    let originalFileName = '';

    if (!selectBtn || !fileInput || !modal || !cropImage) {
        return;
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        cropImage.src = '';
        fileInput.value = '';
    }

    function updatePreview(dataUrl) {
        let previewImg = previewContainer.querySelector('#logo-preview-image');
        const placeholder = previewContainer.querySelector('#logo-placeholder');

        if (!previewImg) {
            previewImg = document.createElement('img');
            previewImg.id = 'logo-preview-image';
            previewImg.alt = 'Company logo';
            previewImg.className = 'max-h-full max-w-full object-contain';
            previewContainer.innerHTML = '';
            previewContainer.appendChild(previewImg);
        }

        previewImg.src = dataUrl;

        if (placeholder) {
            placeholder.style.display = 'none';
        }

        // Uncheck remove checkbox if it exists
        if (removeCheckbox) {
            removeCheckbox.checked = false;
        }

        // Visual feedback
        previewContainer.style.borderColor = '#4f46e5';
        previewContainer.style.borderStyle = 'solid';
        setTimeout(() => {
            previewContainer.style.borderColor = '';
            previewContainer.style.borderStyle = '';
        }, 1500);
    }

    // Select button click
    selectBtn.addEventListener('click', function() {
        fileInput.click();
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, or WebP).');
            fileInput.value = '';
            return;
        }

        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB.');
            fileInput.value = '';
            return;
        }

        originalFileName = file.name;

        const reader = new FileReader();
        reader.onload = function(event) {
            cropImage.src = event.target.result;
            openModal();

            // Initialize cropper after image loads
            cropImage.onload = function() {
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 2,
                    dragMode: 'move',
                    autoCropArea: 0.9,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    minCropBoxWidth: 50,
                    minCropBoxHeight: 50,
                });
            };
        };
        reader.readAsDataURL(file);
    });

    // Zoom controls
    zoomInBtn?.addEventListener('click', function() {
        if (cropper) cropper.zoom(0.1);
    });

    zoomOutBtn?.addEventListener('click', function() {
        if (cropper) cropper.zoom(-0.1);
    });

    rotateBtn?.addEventListener('click', function() {
        if (cropper) cropper.rotate(90);
    });

    resetBtn?.addEventListener('click', function() {
        if (cropper) cropper.reset();
    });

    // Apply crop
    applyBtn?.addEventListener('click', function() {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            alert('Failed to crop image. Please try again.');
            return;
        }

        const dataUrl = canvas.toDataURL('image/png', 0.9);

        // Store cropped data in hidden input
        croppedDataInput.value = dataUrl;

        // Update preview
        updatePreview(dataUrl);

        closeModal();
    });

    // Close modal handlers
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modalBackdrop?.addEventListener('click', closeModal);

    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Handle remove checkbox
    if (removeCheckbox) {
        removeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                croppedDataInput.value = '';
                const previewImg = previewContainer.querySelector('#logo-preview-image');
                if (previewImg) {
                    previewImg.style.opacity = '0.3';
                }
            } else {
                const previewImg = previewContainer.querySelector('#logo-preview-image');
                if (previewImg) {
                    previewImg.style.opacity = '1';
                }
            }
        });
    }
})();
</script>
@endpush
@endsection
