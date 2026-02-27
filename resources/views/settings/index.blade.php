@extends('layouts.dashboard-modern')

@section('title', 'Settings')
@section('page_heading', $settingsPageHeading ?? 'Settings')

@section('content')
    @php
        $canManageCompanyDetails = auth()->user()?->hasRole(\App\Enums\UserRole::ADMIN);
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
                    <p class="text-xs font-semibold uppercase tracking-[0.12em]" style="color: var(--hr-text-muted);">Brand System</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $companyLogoUrl ? 'Visual Ready' : 'Logo Pending' }}</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">Palette + Typography in sync</p>
                    <div class="flex items-center gap-2 mt-3">
                        <span class="inline-flex items-center gap-1">
                            <span class="h-5 w-5 rounded-full border" style="background: {{ $companySettings['brand_primary_color'] }}; border-color: var(--hr-line);"></span>
                            <span class="text-[11px] font-semibold">{{ $companySettings['brand_primary_color'] }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <span class="h-5 w-5 rounded-full border" style="background: {{ $companySettings['brand_secondary_color'] }}; border-color: var(--hr-line);"></span>
                            <span class="text-[11px] font-semibold">{{ $companySettings['brand_secondary_color'] }}</span>
                        </span>
                    </div>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(207 250 254 / 0.4); color: #0e7490;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 3-11 18"></path><path d="M9 3h5v5"></path><path d="M5 19h5v5"></path></svg>
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
            @if(false)
            @if(false)
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
            @endif
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h4 class="text-sm font-extrabold">Brand Identity</h4>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Set brand palette and typography for dashboards and emails.</p>
                    </div>
                </div>
                @php
                    $currentPrimaryColor = strtoupper((string) old('brand_primary_color', $companySettings['brand_primary_color']));
                    if (! preg_match('/^#[0-9A-F]{6}$/', $currentPrimaryColor)) {
                        $currentPrimaryColor = '#7C3AED';
                    }
                    $currentSecondaryColor = strtoupper((string) old('brand_secondary_color', $companySettings['brand_secondary_color']));
                    if (! preg_match('/^#[0-9A-F]{6}$/', $currentSecondaryColor)) {
                        $currentSecondaryColor = '#5EEAD4';
                    }
                    $brandColorPresets = [
                        ['name' => 'Violet + Mint', 'primary' => '#7C3AED', 'secondary' => '#5EEAD4'],
                        ['name' => 'Royal + Aqua', 'primary' => '#2563EB', 'secondary' => '#14B8A6'],
                        ['name' => 'Indigo + Amber', 'primary' => '#4F46E5', 'secondary' => '#F59E0B'],
                        ['name' => 'Emerald + Slate', 'primary' => '#059669', 'secondary' => '#334155'],
                        ['name' => 'Rose + Navy', 'primary' => '#E11D48', 'secondary' => '#1E3A8A'],
                        ['name' => 'Orange + Charcoal', 'primary' => '#EA580C', 'secondary' => '#1F2937'],
                    ];
                @endphp
                <div class="mt-4 rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Quick Color Combinations</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">Choose a balanced preset or fine-tune with the color pickers below.</p>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                        @foreach ($brandColorPresets as $preset)
                            <button
                                type="button"
                                class="rounded-xl border px-2.5 py-2 text-left text-xs font-semibold flex items-center gap-2"
                                style="border-color: var(--hr-line); background: var(--hr-surface-strong);"
                                data-brand-preset
                                data-brand-preset-primary="{{ $preset['primary'] }}"
                                data-brand-preset-secondary="{{ $preset['secondary'] }}"
                            >
                                <span class="inline-flex items-center gap-1 shrink-0">
                                    <span class="h-4 w-4 rounded-full border" style="background: {{ $preset['primary'] }}; border-color: var(--hr-line);"></span>
                                    <span class="h-4 w-4 rounded-full border" style="background: {{ $preset['secondary'] }}; border-color: var(--hr-line);"></span>
                                </span>
                                <span>{{ $preset['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="brand_primary_color" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Primary Color</label>
                        <div class="flex items-center gap-2">
                            <input
                                id="brand_primary_color"
                                name="brand_primary_color"
                                type="text"
                                value="{{ $currentPrimaryColor }}"
                                class="flex-1 rounded-xl border px-3 py-2.5 bg-transparent"
                                style="border-color: var(--hr-line);"
                                placeholder="#7C3AED"
                                data-brand-color-text="primary"
                            >
                            <input
                                id="brand_primary_color_picker"
                                type="color"
                                value="{{ $currentPrimaryColor }}"
                                class="h-10 w-12 rounded-lg border cursor-pointer p-1"
                                style="border-color: var(--hr-line); background: var(--hr-surface);"
                                data-brand-color-picker="primary"
                                aria-label="Choose primary brand color"
                            >
                        </div>
                        <p class="mt-1 text-[11px]" style="color: var(--hr-text-muted);">Use picker for full palette (millions of colors) or paste a hex value.</p>
                        @error('brand_primary_color')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="brand_secondary_color" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Secondary Color</label>
                        <div class="flex items-center gap-2">
                            <input
                                id="brand_secondary_color"
                                name="brand_secondary_color"
                                type="text"
                                value="{{ $currentSecondaryColor }}"
                                class="flex-1 rounded-xl border px-3 py-2.5 bg-transparent"
                                style="border-color: var(--hr-line);"
                                placeholder="#5EEAD4"
                                data-brand-color-text="secondary"
                            >
                            <input
                                id="brand_secondary_color_picker"
                                type="color"
                                value="{{ $currentSecondaryColor }}"
                                class="h-10 w-12 rounded-lg border cursor-pointer p-1"
                                style="border-color: var(--hr-line); background: var(--hr-surface);"
                                data-brand-color-picker="secondary"
                                aria-label="Choose secondary brand color"
                            >
                        </div>
                        <p class="mt-1 text-[11px]" style="color: var(--hr-text-muted);">Pick a complementary accent to keep contrast and readability balanced.</p>
                        @error('brand_secondary_color')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="brand_font_family" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Interface Font</label>
                        <select id="brand_font_family" name="brand_font_family" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                            @php
                                $currentFont = old('brand_font_family', $companySettings['brand_font_family']);
                            @endphp
                            @foreach($brandFontOptions as $fontKey => $fontMeta)
                                <option value="{{ $fontKey }}" @selected($currentFont === $fontKey) style="font-family: {{ $fontMeta['stack'] }};">{{ $fontMeta['label'] }}</option>
                            @endforeach
                        </select>
                        @error('brand_font_family')
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
            <div class="md:col-span-2 rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h4 class="text-sm font-extrabold">Branch Directory</h4>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Branch addresses, contact details, and timezones reused across modules and documents.</p>
                    </div>
                    <button type="button" id="branchDirectoryAdd" class="rounded-lg px-3 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">
                        Add Branch Address
                    </button>
                </div>
                <div class="mt-3">
                    <p class="text-xs" style="color: var(--hr-text-muted);">Mark one branch as primary to show up on payroll slips, onboarding emails, and compliance PDFs.</p>
                    @error('branch_directory')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div id="branchDirectoryList" class="mt-4 space-y-3" data-next-index="{{ count($branchDirectoryEntries ?? []) }}" data-max="{{ $branchDirectoryLimit }}">
                    <p data-branch-empty class="{{ ($branchDirectoryCount ?? 0) > 0 ? 'hidden' : '' }} text-sm" style="color: var(--hr-text-muted);">
                        No branch addresses yet. Click “Add Branch Address” to register an office or region.
                    </p>
                    @foreach(($branchDirectoryEntries ?? []) as $idx => $branchEntry)
                        @php
                            $branchLabel = old("branch_directory.$idx.label", $branchEntry['label'] ?? '');
                            $branchCode = old("branch_directory.$idx.code", $branchEntry['code'] ?? '');
                            $branchPhone = old("branch_directory.$idx.phone", $branchEntry['phone'] ?? '');
                            $branchTimezone = old("branch_directory.$idx.timezone", $branchEntry['timezone'] ?? '');
                            $branchAddress = old("branch_directory.$idx.address", $branchEntry['address'] ?? '');
                            $branchPrimary = (bool) old("branch_directory.$idx.is_primary", $branchEntry['is_primary'] ?? false);
                        @endphp
                        <div class="rounded-xl border p-3 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface);" data-branch-entry>
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div>
                                    <p class="text-sm font-semibold">Branch {{ $idx + 1 }}</p>
                                    <p class="text-xs" style="color: var(--hr-text-muted);">Used in letters, payroll, and compliance exports.</p>
                                </div>
                                <button type="button" class="text-xs font-semibold text-red-600" data-branch-remove>Remove</button>
                            </div>
                            <div class="grid md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Display Label</label>
                                    <input name="branch_directory[{{ $idx }}][label]" type="text" value="{{ $branchLabel }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="New York HQ">
                                    @error("branch_directory.$idx.label")
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Branch Code</label>
                                    <input name="branch_directory[{{ $idx }}][code]" type="text" value="{{ $branchCode }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="NY-HQ">
                                    @error("branch_directory.$idx.code")
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Phone</label>
                                    <input name="branch_directory[{{ $idx }}][phone]" type="text" value="{{ $branchPhone }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="+1 555 0100">
                                    @error("branch_directory.$idx.phone")
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Timezone</label>
                                    <select name="branch_directory[{{ $idx }}][timezone]" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                        <option value="">Select timezone</option>
        @foreach($timezoneOptions as $timezoneValue => $timezoneLabel)
                                        <option value="{{ $timezoneValue }}" {{ $branchTimezone === $timezoneValue ? 'selected' : '' }}>{{ $timezoneLabel }}</option>
        @endforeach
                                    </select>
                                    @error("branch_directory.$idx.timezone")
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Address</label>
                                <textarea name="branch_directory[{{ $idx }}][address]" rows="2" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);" placeholder="123 Main Street, City, Country">{{ $branchAddress }}</textarea>
                                @error("branch_directory.$idx.address")
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold">
                                <input type="hidden" name="branch_directory[{{ $idx }}][is_primary]" value="0">
                                <input type="checkbox" name="branch_directory[{{ $idx }}][is_primary]" value="1" {{ $branchPrimary ? 'checked' : '' }} class="rounded border" style="border-color: var(--hr-line);">
                                Primary Branch
                            </label>
                        </div>
                    @endforeach
                </div>
                <template id="branchDirectoryTemplate">
                    <div class="rounded-xl border p-3 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface);" data-branch-entry>
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <p class="text-sm font-semibold">Branch __INDEX_DISPLAY__</p>
                                <p class="text-xs" style="color: var(--hr-text-muted);">Used across downstream modules.</p>
                            </div>
                            <button type="button" class="text-xs font-semibold text-red-600" data-branch-remove>Remove</button>
                        </div>
                        <div class="grid md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Display Label</label>
                                <input name="branch_directory[__INDEX__][label]" type="text" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="New Branch">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Branch Code</label>
                                <input name="branch_directory[__INDEX__][code]" type="text" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="HQ-01">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Phone</label>
                                <input name="branch_directory[__INDEX__][phone]" type="text" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="+1 555 0123">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Timezone</label>
                                <select name="branch_directory[__INDEX__][timezone]" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                    <option value="">Select timezone</option>
            @foreach($timezoneOptions as $timezoneValue => $timezoneLabel)
                                    <option value="{{ $timezoneValue }}">{{ $timezoneLabel }}</option>
            @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-1" style="color: var(--hr-text-muted);">Address</label>
                            <textarea name="branch_directory[__INDEX__][address]" rows="2" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);" placeholder="Street, City, Country"></textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold">
                            <input type="hidden" name="branch_directory[__INDEX__][is_primary]" value="0">
                            <input type="checkbox" name="branch_directory[__INDEX__][is_primary]" value="1" class="rounded border" style="border-color: var(--hr-line);">
                            Primary Branch
                        </label>
                    </div>
                </template>
            </div>
            @endif
            @endif

            @if ($isSystemSection)
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
            @endif

            <div class="md:col-span-2 mt-1 flex flex-wrap items-center gap-2">
                @if ($canManageCompanyDetails)
                    <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">{{ $isSystemSection ? 'Save System Settings' : 'Save Company Details' }}</button>
                @endif
                <button type="reset" class="rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">Reset</button>
                <span class="text-xs" style="color: var(--hr-text-muted);">Saved values are loaded from the database.</span>
            </div>
        </form>
    </section>
    @endif
@push('scripts')
    <script>
        (() => {
            const list = document.getElementById('branchDirectoryList');
            const addButton = document.getElementById('branchDirectoryAdd');
            const template = document.getElementById('branchDirectoryTemplate');
            if (!list || !addButton || !template) {
                return;
            }

            const maxEntries = Number(list.dataset.max || '8');
            let nextIndex = Number(list.dataset.nextIndex || '0');

            const updateEmptyState = () => {
                const hasEntries = list.querySelectorAll('[data-branch-entry]').length > 0;
                const empty = list.querySelector('[data-branch-empty]');
                if (empty) {
                    empty.classList.toggle('hidden', hasEntries);
                }
                addButton.disabled = list.querySelectorAll('[data-branch-entry]').length >= maxEntries;
            };

            const createBranchEntry = () => {
                if (list.querySelectorAll('[data-branch-entry]').length >= maxEntries) {
                    addButton.disabled = true;
                    return;
                }

                const html = template.innerHTML
                    .replace(/__INDEX__/g, nextIndex.toString())
                    .replace(/__INDEX_DISPLAY__/g, (nextIndex + 1).toString());
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const entry = wrapper.firstElementChild;
                if (entry) {
                    list.appendChild(entry);
                    nextIndex += 1;
                    updateEmptyState();
                }
            };

            addButton.addEventListener('click', () => createBranchEntry());

            list.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                if (target.closest('[data-branch-remove]')) {
                    const entry = target.closest('[data-branch-entry]');
                    if (entry) {
                        entry.remove();
                        updateEmptyState();
                    }
                }
            });

            updateEmptyState();
        })();

        (() => {
            const primaryText = document.querySelector('[data-brand-color-text="primary"]');
            const secondaryText = document.querySelector('[data-brand-color-text="secondary"]');
            const primaryPicker = document.querySelector('[data-brand-color-picker="primary"]');
            const secondaryPicker = document.querySelector('[data-brand-color-picker="secondary"]');
            const presetButtons = document.querySelectorAll('[data-brand-preset]');

            if (
                !(primaryText instanceof HTMLInputElement)
                || !(secondaryText instanceof HTMLInputElement)
                || !(primaryPicker instanceof HTMLInputElement)
                || !(secondaryPicker instanceof HTMLInputElement)
            ) {
                return;
            }

            const hexPattern = /^#[0-9A-F]{6}$/i;
            const normalizeHex = (value, fallback) => {
                const normalized = String(value || '').trim().toUpperCase();
                return hexPattern.test(normalized) ? normalized : fallback;
            };

            const applyPrimary = (value) => {
                const color = normalizeHex(value, '#7C3AED');
                primaryText.value = color;
                primaryPicker.value = color;
            };

            const applySecondary = (value) => {
                const color = normalizeHex(value, '#5EEAD4');
                secondaryText.value = color;
                secondaryPicker.value = color;
            };

            const setActivePreset = () => {
                const primaryValue = normalizeHex(primaryText.value, '#7C3AED');
                const secondaryValue = normalizeHex(secondaryText.value, '#5EEAD4');

                presetButtons.forEach((button) => {
                    if (!(button instanceof HTMLButtonElement)) {
                        return;
                    }

                    const presetPrimary = normalizeHex(button.dataset.brandPresetPrimary, '');
                    const presetSecondary = normalizeHex(button.dataset.brandPresetSecondary, '');
                    const isActive = presetPrimary === primaryValue && presetSecondary === secondaryValue;

                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    button.style.outline = isActive ? '2px solid var(--hr-accent)' : '';
                    button.style.outlineOffset = isActive ? '1px' : '';
                });
            };

            applyPrimary(primaryText.value);
            applySecondary(secondaryText.value);
            setActivePreset();

            primaryPicker.addEventListener('input', () => {
                applyPrimary(primaryPicker.value);
                setActivePreset();
            });
            secondaryPicker.addEventListener('input', () => {
                applySecondary(secondaryPicker.value);
                setActivePreset();
            });

            primaryText.addEventListener('input', () => {
                if (hexPattern.test(primaryText.value.trim())) {
                    applyPrimary(primaryText.value);
                    setActivePreset();
                }
            });
            secondaryText.addEventListener('input', () => {
                if (hexPattern.test(secondaryText.value.trim())) {
                    applySecondary(secondaryText.value);
                    setActivePreset();
                }
            });

            primaryText.addEventListener('blur', () => applyPrimary(primaryText.value));
            secondaryText.addEventListener('blur', () => applySecondary(secondaryText.value));

            presetButtons.forEach((button) => {
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }

                button.addEventListener('click', () => {
                    applyPrimary(button.dataset.brandPresetPrimary);
                    applySecondary(button.dataset.brandPresetSecondary);
                    setActivePreset();
                });
            });
        })();
    </script>
@endpush
@endsection
