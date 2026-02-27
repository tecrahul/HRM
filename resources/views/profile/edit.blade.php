@extends('layouts.dashboard-modern')

@section('title', 'Profile')
@section('page_heading', 'Profile')

@push('head')
    <style>
        .profile-jump-target {
            scroll-margin-top: 110px;
        }

        .profile-jump-target.is-emphasized {
            animation: profileJumpFocus 650ms ease-out;
        }

        .twofa-step-panel {
            overflow: hidden;
            transition: max-height 260ms ease, opacity 220ms ease;
        }

        .twofa-step.is-open .twofa-step-chevron {
            transform: rotate(180deg);
        }

        @keyframes profileJumpFocus {
            0% {
                transform: translateY(14px);
                opacity: 0.82;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $profile = $user?->profile;
        $role = $user?->role;
        $roleLabel = $role instanceof \App\Enums\UserRole ? $role->label() : ucfirst((string) $role);
        $twoFactorEnabled = (bool) ($user?->hasTwoFactorEnabled());
        $twoFactorFeatureEnabled = (bool) ($twoFactorFeatureEnabled ?? true);
        $avatarUrl = $profile?->avatar_url ?? null;
        if (blank($avatarUrl)) {
            $avatarUrl = asset('images/user-avatar.svg');
        } elseif (! str_starts_with((string) $avatarUrl, 'http')) {
            $avatarUrl = asset((string) $avatarUrl);
        }
        $lastPasswordChangeLabel = $lastPasswordChangedAt?->format('M d, Y h:i A') ?? 'Not changed yet';
        $passwordHealthLabel = $lastPasswordChangedAt ? 'Updated' : 'Pending';
        $passwordHealthTone = $lastPasswordChangedAt ? '#16a34a' : '#b45309';
        $passwordHealthBackground = $lastPasswordChangedAt ? 'rgb(34 197 94 / 0.14)' : 'rgb(245 158 11 / 0.16)';
        $mfaStatusLabel = $twoFactorEnabled ? 'Enabled' : 'Disabled';
        $mfaStatusTone = $twoFactorEnabled ? '#16a34a' : '#b45309';
        $mfaStatusBackground = $twoFactorEnabled ? 'rgb(34 197 94 / 0.14)' : 'rgb(245 158 11 / 0.16)';
        $mfaMetaLabel = $twoFactorEnabled
            ? 'Enabled on '.($user?->two_factor_enabled_at?->format('M d, Y h:i A') ?? 'Unknown')
            : 'Protect your account with authenticator-based verification.';
    @endphp

    <section class="mb-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
            <button
                type="button"
                data-profile-jump-target="profileChangePasswordSection"
                class="w-full text-left rounded-xl border p-3 transition hover:-translate-y-0.5"
                style="border-color: var(--hr-line); background: var(--hr-surface-strong);"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold">Password Reset</p>
                    <span class="text-[10px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="color: {{ $passwordHealthTone }}; background: {{ $passwordHealthBackground }};">
                        {{ $passwordHealthLabel }}
                    </span>
                </div>
                <p class="text-xs mt-1.5" style="color: var(--hr-text-muted);">Last changed: {{ $lastPasswordChangeLabel }}</p>
                <p class="text-[11px] mt-1" style="color: var(--hr-text-muted);">Opens Password Reset form.</p>
            </button>

            <button
                type="button"
                data-profile-jump-target="profileMfaSection"
                class="w-full text-left rounded-xl border p-3 transition hover:-translate-y-0.5"
                style="border-color: var(--hr-line); background: var(--hr-surface-strong);"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold">Multi-Factor Authentication</p>
                    <span class="text-[10px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="color: {{ $mfaStatusTone }}; background: {{ $mfaStatusBackground }};">
                        {{ $mfaStatusLabel }}
                    </span>
                </div>
                <p class="text-xs mt-1.5" style="color: var(--hr-text-muted);">{{ $mfaMetaLabel }}</p>
                <p class="text-[11px] mt-1" style="color: var(--hr-text-muted);">Opens MFA setup section.</p>
            </button>
        </div>
    </section>

    @php
        $twoFactorStatusMessage = (string) session('two_factor_status', '');
        $twoFactorJustEnabled = $twoFactorEnabled && str_contains(strtolower($twoFactorStatusMessage), 'enabled successfully');
        $twoFactorQrCodeUrl = isset($twoFactorSetup['otpauth_uri'])
            ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data='.rawurlencode((string) $twoFactorSetup['otpauth_uri'])
            : null;
        $autoProfileScrollTarget = (string) session('profile_scroll_target', '');
        if (
            $autoProfileScrollTarget === ''
            && (
                $errors->twoFactorEnable->any()
                || $errors->twoFactorDisable->any()
                || $errors->twoFactorRecoveryCodes->any()
            )
        ) {
            $autoProfileScrollTarget = 'profileMfaSection';
        }
    @endphp

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-1">
            <div class="flex flex-col items-center text-center">
                <div class="relative group">
                    <img id="profileAvatarPreview" src="{{ $avatarUrl }}" alt="Profile avatar" class="h-28 w-28 rounded-2xl object-cover border" style="border-color: var(--hr-line);">
                    <label for="avatar" class="absolute inset-0 rounded-2xl flex items-center justify-center text-xs font-semibold text-white cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity" style="background: rgb(2 8 23 / 0.45);">
                        Change Photo
                    </label>
                </div>
                <h3 class="mt-3 text-lg font-extrabold">{{ $user?->name }}</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">{{ $user?->email }}</p>
            </div>

            <div class="mt-5 space-y-3 text-sm">
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Role</p>
                    <p class="mt-1 font-semibold">{{ $roleLabel }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Department</p>
                    <p class="mt-1 font-semibold">{{ $profile?->department ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Branch</p>
                    <p class="mt-1 font-semibold">{{ $profile?->branch ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Job Title</p>
                    <p class="mt-1 font-semibold">{{ $profile?->job_title ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Work Location</p>
                    <p class="mt-1 font-semibold">{{ $profile?->work_location ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Reporting Manager</p>
                    <p class="mt-1 font-semibold">{{ $profile?->manager_name ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-xl p-3 border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Joined On</p>
                    <p class="mt-1 font-semibold">{{ $profile?->joined_on?->format('M d, Y') ?? 'Not available' }}</p>
                </div>
            </div>
        </article>

        <article id="profileEditSection" class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2 profile-jump-target">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-extrabold">Edit Profile</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Update your personal and company-standard employee details.</p>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent); border: 1px solid var(--hr-line);">Secure</span>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #22c55e55; background: #22c55e12; color: #166534;">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                    Please correct the highlighted fields and submit again.
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PUT')
                <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="hidden">
                <div id="avatarClientError" class="hidden md:col-span-2 rounded-xl px-3 py-2 text-xs border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;"></div>

                <div>
                    <label for="first_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">First Name</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user?->first_name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('first_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Last Name</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user?->last_name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('last_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="middle_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Middle Name (Optional)</label>
                    <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $user?->middle_name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('middle_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Email</label>
                    <input id="email" type="email" value="{{ $user?->email }}" readonly class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $profile?->phone) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('phone')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="alternate_phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Alternate Phone</label>
                    <input id="alternate_phone" name="alternate_phone" type="text" value="{{ old('alternate_phone', $profile?->alternate_phone) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('alternate_phone')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date_of_birth" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Date of Birth</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d')) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('date_of_birth')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gender" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Gender</label>
                    @php
                        $selectedGender = old('gender', $profile?->gender);
                    @endphp
                    <select id="gender" name="gender" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        <option value="">Select</option>
                        <option value="male" {{ $selectedGender === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $selectedGender === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ $selectedGender === 'other' ? 'selected' : '' }}>Other</option>
                        <option value="prefer_not_to_say" {{ $selectedGender === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                    </select>
                    @error('gender')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="marital_status" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Marital Status</label>
                    @php
                        $selectedMaritalStatus = old('marital_status', $profile?->marital_status);
                    @endphp
                    <select id="marital_status" name="marital_status" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        <option value="">Select</option>
                        <option value="single" {{ $selectedMaritalStatus === 'single' ? 'selected' : '' }}>Single</option>
                        <option value="married" {{ $selectedMaritalStatus === 'married' ? 'selected' : '' }}>Married</option>
                        <option value="divorced" {{ $selectedMaritalStatus === 'divorced' ? 'selected' : '' }}>Divorced</option>
                        <option value="widowed" {{ $selectedMaritalStatus === 'widowed' ? 'selected' : '' }}>Widowed</option>
                        <option value="prefer_not_to_say" {{ $selectedMaritalStatus === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                    </select>
                    @error('marital_status')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nationality" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Nationality</label>
                    <input id="nationality" name="nationality" type="text" value="{{ old('nationality', $profile?->nationality) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('nationality')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="national_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">National ID / Passport</label>
                    <input id="national_id" name="national_id" type="text" value="{{ old('national_id', $profile?->national_id) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('national_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="linkedin_url" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">LinkedIn URL</label>
                    <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $profile?->linkedin_url) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('linkedin_url')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="emergency_contact_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Emergency Contact Name</label>
                    <input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $profile?->emergency_contact_name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('emergency_contact_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="emergency_contact_phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Emergency Contact Phone</label>
                    <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" value="{{ old('emergency_contact_phone', $profile?->emergency_contact_phone) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('emergency_contact_phone')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Address</label>
                    <textarea id="address" name="address" rows="3" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('address', $profile?->address) }}</textarea>
                    @error('address')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-2 pt-1">
                    <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">Save Changes</button>
                    <a href="{{ route($user?->dashboardRouteName() ?? 'dashboard') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">Back to Dashboard</a>
                </div>
            </form>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article id="profileChangePasswordSection" class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2 profile-jump-target">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-extrabold">Change Password</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Update your account password from dashboard using your current password.</p>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent); border: 1px solid var(--hr-line);">Secure</span>
            </div>

            @if (session('password_status'))
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #22c55e55; background: #22c55e12; color: #166534;">
                    {{ session('password_status') }}
                </div>
            @endif

            @if ($errors->passwordUpdate->any())
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                    Please fix the password fields and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @method('PUT')

                <div class="md:col-span-2">
                    <label for="current_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Current Password</label>
                    <div class="relative">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-xl border px-3 py-2.5 pr-10 bg-transparent" style="border-color: var(--hr-line);">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100/60 hover:text-slate-700" data-password-toggle data-target="current_password" data-visible="false" aria-label="Show password">
                            <svg class="icon-shown h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-hidden hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-4 4.9"></path>
                                <path d="M6.6 6.6C4 8.3 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 5.2-1.4"></path>
                            </svg>
                        </button>
                    </div>
                    @error('current_password', 'passwordUpdate')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">New Password</label>
                    <div class="relative">
                        <input id="new_password" name="password" type="password" autocomplete="new-password" class="w-full rounded-xl border px-3 py-2.5 pr-10 bg-transparent" style="border-color: var(--hr-line);">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100/60 hover:text-slate-700" data-password-toggle data-target="new_password" data-visible="false" aria-label="Show password">
                            <svg class="icon-shown h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-hidden hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-4 4.9"></path>
                                <path d="M6.6 6.6C4 8.3 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 5.2-1.4"></path>
                            </svg>
                        </button>
                    </div>
                    @error('password', 'passwordUpdate')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Confirm New Password</label>
                    <div class="relative">
                        <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border px-3 py-2.5 pr-10 bg-transparent" style="border-color: var(--hr-line);">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100/60 hover:text-slate-700" data-password-toggle data-target="new_password_confirmation" data-visible="false" aria-label="Show password">
                            <svg class="icon-shown h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-hidden hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-4 4.9"></path>
                                <path d="M6.6 6.6C4 8.3 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 5.2-1.4"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-2 pt-1">
                    <button id="passwordUpdateSubmit" type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-55" style="background: linear-gradient(120deg, #7c3aed, #ec4899);" disabled aria-disabled="true">Update Password</button>
                </div>
            </form>
        </article>

        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-1">
            <h3 class="text-base font-extrabold">Password Guidance</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex items-start gap-2 text-red-600" data-password-rule="length">
                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg data-icon-pass class="hidden h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 10l4 4 8-8"></path>
                        </svg>
                        <svg data-icon-fail class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5l10 10"></path>
                            <path d="M15 5L5 15"></path>
                        </svg>
                    </span>
                    <span>Use minimum 8 characters.</span>
                </li>
                <li class="flex items-start gap-2 text-red-600" data-password-rule="upper">
                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg data-icon-pass class="hidden h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 10l4 4 8-8"></path>
                        </svg>
                        <svg data-icon-fail class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5l10 10"></path>
                            <path d="M15 5L5 15"></path>
                        </svg>
                    </span>
                    <span>Include at least 1 uppercase letter (A-Z).</span>
                </li>
                <li class="flex items-start gap-2 text-red-600" data-password-rule="lower">
                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg data-icon-pass class="hidden h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 10l4 4 8-8"></path>
                        </svg>
                        <svg data-icon-fail class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5l10 10"></path>
                            <path d="M15 5L5 15"></path>
                        </svg>
                    </span>
                    <span>Include at least 1 lowercase letter (a-z).</span>
                </li>
                <li class="flex items-start gap-2 text-red-600" data-password-rule="number">
                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg data-icon-pass class="hidden h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 10l4 4 8-8"></path>
                        </svg>
                        <svg data-icon-fail class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5l10 10"></path>
                            <path d="M15 5L5 15"></path>
                        </svg>
                    </span>
                    <span>Include at least 1 number (0-9).</span>
                </li>
                <li class="flex items-start gap-2 text-red-600" data-password-rule="special">
                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg data-icon-pass class="hidden h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 10l4 4 8-8"></path>
                        </svg>
                        <svg data-icon-fail class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 5l10 10"></path>
                            <path d="M15 5L5 15"></path>
                        </svg>
                    </span>
                    <span>Include at least 1 special character (for example: ! @ # $ %).</span>
                </li>
            </ul>
            <ul class="mt-3 space-y-2 text-sm" style="color: var(--hr-text-muted);">
                <li>Choose a unique password not used in other apps.</li>
                <li>Do not share your password with anyone.</li>
            </ul>
        </article>
    </section>

    <section class="mt-5 grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article id="profileMfaSection" class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2 profile-jump-target">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-extrabold">Two-Factor Authentication</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Add an extra verification step to secure your account.</p>
                </div>
                <span
                    class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1"
                    style="border: 1px solid var(--hr-line); color: {{ $twoFactorEnabled ? '#166534' : '#b45309' }}; background: {{ $twoFactorEnabled ? 'rgb(34 197 94 / 0.16)' : 'rgb(245 158 11 / 0.18)' }};"
                >
                    {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            @if ($twoFactorJustEnabled)
                <div class="mt-4 rounded-xl border p-4" style="border-color: #22c55e66; background: #ecfdf5;">
                    <p class="text-sm font-semibold" style="color: #166534;">Step 3 of 3: Two-Factor Authentication Enabled</p>
                    <p class="text-xs mt-1" style="color: #166534;">
                        Great. Your account now requires both password and authenticator code at sign in.
                    </p>
                </div>
            @elseif ($twoFactorStatusMessage !== '')
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #22c55e55; background: #22c55e12; color: #166534;">
                    {{ $twoFactorStatusMessage }}
                </div>
            @endif

            @if (! $twoFactorFeatureEnabled)
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #f59e0b55; background: #f59e0b12; color: #92400e;">
                    Two-factor authentication is currently disabled in admin settings.
                </div>
            @endif

            @if ($twoFactorEnabled)
                <div class="mt-4 rounded-xl border p-3 text-sm" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <p class="font-semibold">Status: Active</p>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">
                        Enabled on {{ $user?->two_factor_enabled_at?->format('M d, Y h:i A') ?? 'Unknown date' }}.
                    </p>
                </div>

                @if ($twoFactorFeatureEnabled && ! empty($freshRecoveryCodes))
                    <div class="mt-4 rounded-xl border p-4" style="border-color: var(--hr-accent-border); background: var(--hr-accent-soft);">
                        <p class="text-sm font-semibold" style="color: var(--hr-text-main);">New recovery codes generated</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Save these once-only codes now. They will not be shown again.</p>
                        <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs font-semibold">
                            @foreach($freshRecoveryCodes as $recoveryCode)
                                <code class="rounded-lg border px-2 py-1.5 text-center" style="border-color: var(--hr-line); background: var(--hr-surface); color: var(--hr-text-main);">{{ $recoveryCode }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($twoFactorFeatureEnabled && $errors->twoFactorRecoveryCodes->any())
                    <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                        Recovery codes were not regenerated. Check password and try again.
                    </div>
                @endif

                @if ($errors->twoFactorDisable->any())
                    <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                        Could not disable two-factor authentication. Check password and retry.
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.two-factor.recovery-codes.regenerate') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3" data-twofa-action-form>
                    @csrf
                    <div class="md:col-span-2">
                        <label for="two_factor_action_current_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Current Password</label>
                        <input id="two_factor_action_current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        @error('current_password', 'twoFactorRecoveryCodes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                        @error('current_password', 'twoFactorDisable')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-3 flex flex-wrap items-center gap-2">
                        <button
                            type="submit"
                            formaction="{{ route('profile.two-factor.recovery-codes.regenerate') }}"
                            class="rounded-xl px-3.5 py-2 text-sm font-semibold border disabled:opacity-55 disabled:cursor-not-allowed"
                            style="border-color: var(--hr-line);"
                            data-twofa-password-required
                        >
                            Regenerate Recovery Codes
                        </button>
                        <button
                            type="submit"
                            formaction="{{ route('profile.two-factor.disable') }}"
                            class="rounded-xl px-3.5 py-2 text-sm font-semibold text-red-700 border border-red-300 bg-red-50 hover:bg-red-100 disabled:opacity-55 disabled:cursor-not-allowed"
                            data-twofa-password-required
                        >
                            Disable 2FA
                        </button>
                        <button
                            type="button"
                            id="two_factor_download_recovery_codes"
                            class="rounded-xl px-3.5 py-2 text-sm font-semibold border disabled:opacity-55 disabled:cursor-not-allowed"
                            style="border-color: var(--hr-line); background: var(--hr-surface-strong);"
                            data-twofa-password-required
                            data-twofa-download
                        >
                            Download Recovery Codes
                        </button>
                    </div>
                </form>
                @if (! empty($freshRecoveryCodes))
                    <script type="application/json" id="twoFactorRecoveryCodesData">@json(array_values($freshRecoveryCodes))</script>
                @endif
            @else
                @if ($twoFactorFeatureEnabled)
                    @php
                        $twoFactorWizardStep = $errors->twoFactorEnable->any() ? 'step2' : 'step1';
                    @endphp
                    <div class="mt-4 space-y-3" data-twofa-wizard>
                        <article class="twofa-step rounded-xl border p-4 {{ $twoFactorWizardStep === 'step1' ? 'is-open' : '' }}" data-twofa-step="step1" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                            <button
                                type="button"
                                class="w-full text-left flex items-center justify-between gap-3"
                                data-twofa-step-trigger
                                aria-expanded="{{ $twoFactorWizardStep === 'step1' ? 'true' : 'false' }}"
                                aria-controls="twofaStep1Panel"
                            >
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-accent);">Step 1 of 3</p>
                                    <p class="text-sm font-semibold mt-1">What is Two-Factor Authentication?</p>
                                </div>
                                <svg class="twofa-step-chevron h-4 w-4 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div
                                id="twofaStep1Panel"
                                data-twofa-step-panel
                                data-open="{{ $twoFactorWizardStep === 'step1' ? 'true' : 'false' }}"
                                class="twofa-step-panel mt-3 {{ $twoFactorWizardStep === 'step1' ? '' : 'pointer-events-none' }}"
                                style="max-height: {{ $twoFactorWizardStep === 'step1' ? '2200px' : '0px' }}; opacity: {{ $twoFactorWizardStep === 'step1' ? '1' : '0' }};"
                            >
                                <p class="text-xs" style="color: var(--hr-text-muted);">
                                    Two-Factor Authentication (2FA) adds one-time code verification after your password. This protects your account even if someone knows your password.
                                </p>

                                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2.5">
                                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noopener" class="rounded-xl border p-3 transition hover:-translate-y-0.5" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white" style="background: linear-gradient(120deg, #2563eb, #22c55e);">GA</span>
                                            <span class="text-sm font-semibold">Google Authenticator</span>
                                        </div>
                                        <p class="text-[11px] mt-2" style="color: var(--hr-text-muted);">Android / iOS</p>
                                    </a>
                                    <a href="https://www.microsoft.com/security/mobile-authenticator-app" target="_blank" rel="noopener" class="rounded-xl border p-3 transition hover:-translate-y-0.5" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white" style="background: linear-gradient(120deg, #2563eb, #0ea5e9);">MS</span>
                                            <span class="text-sm font-semibold">Microsoft Authenticator</span>
                                        </div>
                                        <p class="text-[11px] mt-2" style="color: var(--hr-text-muted);">Android / iOS</p>
                                    </a>
                                    <a href="https://authy.com/download/" target="_blank" rel="noopener" class="rounded-xl border p-3 transition hover:-translate-y-0.5" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white" style="background: linear-gradient(120deg, #7c3aed, #db2777);">AU</span>
                                            <span class="text-sm font-semibold">Authy</span>
                                        </div>
                                        <p class="text-[11px] mt-2" style="color: var(--hr-text-muted);">Android / iOS / Desktop</p>
                                    </a>
                                </div>
                            </div>
                        </article>

                        <article class="twofa-step rounded-xl border p-4 {{ $twoFactorWizardStep === 'step2' ? 'is-open' : '' }}" data-twofa-step="step2" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                            <button
                                type="button"
                                class="w-full text-left flex items-center justify-between gap-3"
                                data-twofa-step-trigger
                                aria-expanded="{{ $twoFactorWizardStep === 'step2' ? 'true' : 'false' }}"
                                aria-controls="twofaStep2Panel"
                            >
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-accent);">Step 2 of 3</p>
                                    <p class="text-sm font-semibold mt-1">Scan the QR and Verify Your Identity</p>
                                </div>
                                <svg class="twofa-step-chevron h-4 w-4 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div
                                id="twofaStep2Panel"
                                data-twofa-step-panel
                                data-open="{{ $twoFactorWizardStep === 'step2' ? 'true' : 'false' }}"
                                class="twofa-step-panel mt-3 {{ $twoFactorWizardStep === 'step2' ? '' : 'pointer-events-none' }}"
                                style="max-height: {{ $twoFactorWizardStep === 'step2' ? '2200px' : '0px' }}; opacity: {{ $twoFactorWizardStep === 'step2' ? '1' : '0' }};"
                            >
                                <p class="text-xs" style="color: var(--hr-text-muted);">
                                    Use your authenticator app to scan the QR code, then enter your account password and current 6-digit authenticator code.
                                </p>

                                <div class="mt-3 grid grid-cols-1 md:grid-cols-[220px_minmax(0,1fr)] gap-4">
                                    <div class="rounded-xl border p-2 flex items-center justify-center" style="border-color: var(--hr-line); background: #fff;">
                                        @if ($twoFactorQrCodeUrl)
                                            <img src="{{ $twoFactorQrCodeUrl }}" alt="2FA QR code" class="h-[200px] w-[200px] object-contain" loading="lazy">
                                        @else
                                            <p class="text-xs text-center" style="color: var(--hr-text-muted);">QR code unavailable</p>
                                        @endif
                                    </div>
                                    <div>
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Secret Key</p>
                                            <code class="mt-1 inline-flex rounded-lg border px-2 py-1 text-xs font-semibold" style="border-color: var(--hr-line); background: var(--hr-surface); color: var(--hr-text-main);">
                                                {{ $twoFactorSetup['secret_formatted'] ?? 'Unavailable' }}
                                            </code>
                                        </div>
                                        <div class="mt-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Manual Setup URI</p>
                                            <input type="text" readonly value="{{ $twoFactorSetup['otpauth_uri'] ?? '' }}" class="mt-1 w-full rounded-xl border px-3 py-2 text-xs" style="border-color: var(--hr-line); background: var(--hr-surface); color: var(--hr-text-main);">
                                        </div>
                                    </div>
                                </div>

                                @if ($errors->twoFactorEnable->any())
                                    <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                                        Could not enable two-factor authentication. Verify the password and authenticator code.
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('profile.two-factor.enable') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @csrf
                                    <div>
                                        <label for="two_factor_enable_current_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Current Password</label>
                                        <input id="two_factor_enable_current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                        @error('current_password', 'twoFactorEnable')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="two_factor_enable_code" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Authenticator Code</label>
                                        <input id="two_factor_enable_code" name="code" type="text" value="{{ old('code') }}" placeholder="123456" autocomplete="one-time-code" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                        @error('code', 'twoFactorEnable')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">Verify and Enable 2FA</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    </div>
                @else
                    <div class="mt-4 rounded-xl border p-4 text-sm" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                        2FA setup is disabled by admin settings.
                    </div>
                @endif
            @endif
        </article>

        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-1">
            <h3 class="text-base font-extrabold">2FA Tips</h3>
            <ul class="mt-3 space-y-2 text-sm" style="color: var(--hr-text-muted);">
                <li>Use apps like Google Authenticator, Authy, or 1Password.</li>
                <li>Recovery codes let you sign in if you lose device access.</li>
                <li>Regenerate recovery codes if you suspect they were exposed.</li>
                <li>Disabling 2FA removes app-code checks on login immediately.</li>
            </ul>
        </article>
    </section>

    <div id="avatarCropModal" class="fixed inset-0 z-[2200] hidden items-center justify-center p-4" style="background: rgb(2 8 23 / 0.72);">
        <div class="w-full max-w-3xl rounded-2xl border p-4 md:p-5" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-base md:text-lg font-extrabold">Crop Profile Photo</h4>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Drag image to reposition and use zoom before applying.</p>
                </div>
                <button id="avatarCropClose" type="button" class="rounded-lg px-2 py-1 text-xs font-semibold border" style="border-color: var(--hr-line);">Close</button>
            </div>

            <div class="mt-4 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_260px] gap-4">
                <div class="rounded-xl border p-3 flex items-center justify-center overflow-hidden" style="border-color: var(--hr-line); background: var(--hr-surface);">
                    <canvas id="avatarCropCanvas" width="420" height="420" class="w-full max-w-[420px] rounded-xl border touch-none" style="border-color: var(--hr-line);"></canvas>
                </div>

                <div class="rounded-xl border p-3 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                    <div>
                        <label for="avatarZoomRange" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">
                            Zoom
                        </label>
                        <div class="hrm-slider">
                            <input id="avatarZoomRange" type="range" min="1" max="3" step="0.01" value="1" class="w-full">
                            <span id="avatarZoomRangeValue" class="hrm-slider__value" role="status" aria-live="polite">1.00x</span>
                        </div>
                    </div>

                    <p class="text-xs" style="color: var(--hr-text-muted);">
                        The cropped image preview updates immediately after clicking Apply.
                    </p>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button id="avatarCropCancel" type="button" class="rounded-xl px-3 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">
                            Cancel
                        </button>
                        <button id="avatarCropApply" type="button" class="rounded-xl px-3 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                            Apply Crop
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const jumpButtons = Array.from(document.querySelectorAll('[data-profile-jump-target]'));
            const autoProfileScrollTarget = @json($autoProfileScrollTarget);
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const emphasizeTarget = (target) => {
                target.classList.remove('is-emphasized');
                void target.offsetWidth;
                target.classList.add('is-emphasized');
                window.setTimeout(() => {
                    target.classList.remove('is-emphasized');
                }, 700);
            };

            jumpButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-profile-jump-target');
                    if (!targetId) {
                        return;
                    }

                    const target = document.getElementById(targetId);
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    target.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'start',
                    });
                    emphasizeTarget(target);
                });
            });

            if (autoProfileScrollTarget) {
                const target = document.getElementById(autoProfileScrollTarget);
                if (target instanceof HTMLElement) {
                    requestAnimationFrame(() => {
                        target.scrollIntoView({
                            behavior: prefersReducedMotion ? 'auto' : 'smooth',
                            block: 'start',
                        });
                        emphasizeTarget(target);
                    });
                }
            }
        })();

        (() => {
            const wizard = document.querySelector('[data-twofa-wizard]');
            if (!(wizard instanceof HTMLElement)) {
                return;
            }

            const steps = Array.from(wizard.querySelectorAll('[data-twofa-step]'));
            if (steps.length === 0) {
                return;
            }

            const setStepOpen = (targetStep, options = {}) => {
                const { immediate = false } = options;

                steps.forEach((step) => {
                    if (!(step instanceof HTMLElement)) {
                        return;
                    }

                    const isOpen = step.dataset.twofaStep === targetStep;
                    const trigger = step.querySelector('[data-twofa-step-trigger]');
                    const panel = step.querySelector('[data-twofa-step-panel]');
                    if (!(panel instanceof HTMLElement)) {
                        return;
                    }

                    step.classList.toggle('is-open', isOpen);
                    panel.dataset.open = isOpen ? 'true' : 'false';
                    panel.classList.toggle('pointer-events-none', !isOpen);
                    panel.style.opacity = isOpen ? '1' : '0';
                    panel.style.maxHeight = isOpen ? `${panel.scrollHeight}px` : '0px';

                    if (trigger instanceof HTMLButtonElement) {
                        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    }

                    if (immediate) {
                        panel.style.transition = 'none';
                        requestAnimationFrame(() => {
                            panel.style.transition = '';
                        });
                    }
                });
            };

            let initialStep = 'step1';
            steps.forEach((step) => {
                if (step instanceof HTMLElement && step.classList.contains('is-open')) {
                    initialStep = step.dataset.twofaStep || initialStep;
                }
            });
            setStepOpen(initialStep, { immediate: true });

            steps.forEach((step) => {
                const trigger = step.querySelector('[data-twofa-step-trigger]');
                if (!(trigger instanceof HTMLButtonElement)) {
                    return;
                }

                trigger.addEventListener('click', () => {
                    const targetStep = step.dataset.twofaStep;
                    if (!targetStep) {
                        return;
                    }
                    setStepOpen(targetStep);
                });
            });

            window.addEventListener('resize', () => {
                const openStep = steps.find((step) => step instanceof HTMLElement && step.classList.contains('is-open'));
                if (!(openStep instanceof HTMLElement)) {
                    return;
                }

                const targetStep = openStep.dataset.twofaStep;
                if (!targetStep) {
                    return;
                }

                setStepOpen(targetStep);
            });
        })();

        (() => {
            const actionForm = document.querySelector('[data-twofa-action-form]');
            if (!(actionForm instanceof HTMLFormElement)) {
                return;
            }

            const passwordInput = actionForm.querySelector('input[name="current_password"]');
            if (!(passwordInput instanceof HTMLInputElement)) {
                return;
            }

            const protectedButtons = Array.from(actionForm.querySelectorAll('[data-twofa-password-required]'))
                .filter((button) => button instanceof HTMLButtonElement);
            if (protectedButtons.length === 0) {
                return;
            }

            const downloadButton = actionForm.querySelector('[data-twofa-download]');
            const recoveryCodesData = document.getElementById('twoFactorRecoveryCodesData');
            let recoveryCodes = [];
            if (recoveryCodesData instanceof HTMLScriptElement) {
                try {
                    const parsed = JSON.parse(recoveryCodesData.textContent || '[]');
                    if (Array.isArray(parsed)) {
                        recoveryCodes = parsed.filter((code) => typeof code === 'string' && code.trim() !== '');
                    }
                } catch (_) {
                    recoveryCodes = [];
                }
            }

            const setButtonsEnabledState = () => {
                const hasPassword = passwordInput.value.trim().length > 0;

                protectedButtons.forEach((button) => {
                    if (!(button instanceof HTMLButtonElement)) {
                        return;
                    }

                    const isDownloadButton = button === downloadButton;
                    const canEnable = isDownloadButton
                        ? recoveryCodes.length > 0
                        : hasPassword;
                    button.disabled = !canEnable;
                    button.setAttribute('aria-disabled', canEnable ? 'false' : 'true');
                });
            };

            passwordInput.addEventListener('input', setButtonsEnabledState);
            setButtonsEnabledState();

            if (downloadButton instanceof HTMLButtonElement) {
                downloadButton.addEventListener('click', () => {
                    if (downloadButton.disabled || recoveryCodes.length === 0) {
                        return;
                    }

                    const timestamp = new Date();
                    const lines = [
                        'HRM Recovery Codes',
                        `Generated: ${timestamp.toISOString()}`,
                        '',
                        ...recoveryCodes,
                        '',
                        'Keep these codes in a safe place.',
                    ];
                    const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
                    const downloadUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = `recovery-codes-${timestamp.toISOString().slice(0, 10)}.txt`;
                    document.body.append(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(downloadUrl);
                });
            }
        })();

        (() => {
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('profileAvatarPreview');
            const clientError = document.getElementById('avatarClientError');
            const cropModal = document.getElementById('avatarCropModal');
            const cropCanvas = document.getElementById('avatarCropCanvas');
            const zoomRange = document.getElementById('avatarZoomRange');
            const zoomRangeValue = document.getElementById('avatarZoomRangeValue');
            const cropApply = document.getElementById('avatarCropApply');
            const cropCancel = document.getElementById('avatarCropCancel');
            const cropClose = document.getElementById('avatarCropClose');

            if (!avatarInput || !avatarPreview || !cropModal || !cropCanvas || !zoomRange || !cropApply || !cropCancel || !cropClose) {
                return;
            }

            const defaultZoomSettings = {
                min: zoomRange.min,
                max: zoomRange.max,
                value: zoomRange.value,
                step: zoomRange.step || '0.01',
            };

            const context = cropCanvas.getContext('2d');
            if (!context) {
                return;
            }

            const state = {
                image: null,
                scale: 1,
                minScale: 1,
                offsetX: 0,
                offsetY: 0,
                dragActive: false,
                dragStartX: 0,
                dragStartY: 0,
            };

            const updateZoomSliderVisuals = () => {
                if (!zoomRange) {
                    return;
                }

                const min = Number.parseFloat(zoomRange.min);
                const max = Number.parseFloat(zoomRange.max);
                const value = Number.parseFloat(zoomRange.value);
                if (Number.isNaN(min) || Number.isNaN(max) || Number.isNaN(value)) {
                    return;
                }

                const rangeSize = Math.max(max - min, 0.0001);
                const percent = ((value - min) / rangeSize) * 100;
                const clampedPercent = Math.min(100, Math.max(0, percent));
                zoomRange.style.setProperty('--hr-slider-fill', `${clampedPercent}%`);

                if (zoomRangeValue instanceof HTMLElement) {
                    zoomRangeValue.textContent = `${value.toFixed(2)}x`;
                }
            };
            updateZoomSliderVisuals();

            let selectedSourceFile = null;
            let previewObjectUrl = null;

            const showError = (message) => {
                if (!clientError) {
                    return;
                }

                if (!message) {
                    clientError.classList.add('hidden');
                    clientError.textContent = '';

                    return;
                }

                clientError.textContent = message;
                clientError.classList.remove('hidden');
            };

            const clampOffsets = () => {
                if (!state.image) {
                    return;
                }

                const drawnWidth = state.image.width * state.scale;
                const drawnHeight = state.image.height * state.scale;
                const minX = cropCanvas.width - drawnWidth;
                const minY = cropCanvas.height - drawnHeight;

                state.offsetX = Math.min(0, Math.max(minX, state.offsetX));
                state.offsetY = Math.min(0, Math.max(minY, state.offsetY));
            };

            const renderCropCanvas = () => {
                context.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
                context.fillStyle = '#0f172a';
                context.fillRect(0, 0, cropCanvas.width, cropCanvas.height);

                if (!state.image) {
                    return;
                }

                const width = state.image.width * state.scale;
                const height = state.image.height * state.scale;
                context.drawImage(state.image, state.offsetX, state.offsetY, width, height);

                context.strokeStyle = 'rgba(255,255,255,0.7)';
                context.lineWidth = 2;
                context.strokeRect(1, 1, cropCanvas.width - 2, cropCanvas.height - 2);
            };

            const setModalOpen = (open) => {
                cropModal.classList.toggle('hidden', !open);
                cropModal.classList.toggle('flex', open);
                document.body.classList.toggle('app-legacy-modal-open', open);
            };

            const resetSelection = () => {
                selectedSourceFile = null;
                state.image = null;
                state.dragActive = false;
                avatarInput.value = '';
                zoomRange.min = defaultZoomSettings.min;
                zoomRange.max = defaultZoomSettings.max;
                zoomRange.step = defaultZoomSettings.step;
                zoomRange.value = defaultZoomSettings.value;
                updateZoomSliderVisuals();
                setModalOpen(false);
                renderCropCanvas();
            };

            const positionForScale = (nextScale) => {
                const centerX = cropCanvas.width / 2;
                const centerY = cropCanvas.height / 2;
                const ratio = nextScale / state.scale;

                state.offsetX = centerX - (centerX - state.offsetX) * ratio;
                state.offsetY = centerY - (centerY - state.offsetY) * ratio;
                state.scale = nextScale;
                clampOffsets();
                renderCropCanvas();
            };

            const loadImageForCrop = (file) => {
                const objectUrl = URL.createObjectURL(file);
                const image = new Image();

                image.onload = () => {
                    URL.revokeObjectURL(objectUrl);
                    state.image = image;
                    state.minScale = Math.max(cropCanvas.width / image.width, cropCanvas.height / image.height);
                    state.scale = state.minScale;
                    state.offsetX = (cropCanvas.width - image.width * state.scale) / 2;
                    state.offsetY = (cropCanvas.height - image.height * state.scale) / 2;
                    clampOffsets();

                    const maxScale = Math.max(state.minScale + 0.15, state.minScale * 3);
                    zoomRange.min = state.minScale.toFixed(2);
                    zoomRange.max = maxScale.toFixed(2);
                    zoomRange.step = '0.01';
                    zoomRange.value = state.scale.toFixed(2);
                    updateZoomSliderVisuals();

                    renderCropCanvas();
                    setModalOpen(true);
                };

                image.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    showError('Selected file could not be read as an image.');
                    resetSelection();
                };

                image.src = objectUrl;
            };

            const replaceAvatarFile = (blob) => {
                const file = new File([blob], `avatar-crop-${Date.now()}.png`, { type: 'image/png' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                avatarInput.files = dataTransfer.files;

                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                }
                previewObjectUrl = URL.createObjectURL(blob);
                avatarPreview.src = previewObjectUrl;
            };

            avatarInput.addEventListener('change', () => {
                showError('');

                const file = avatarInput.files?.[0];
                if (!file) {
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showError('Only JPG, PNG, or WEBP images are allowed.');
                    resetSelection();

                    return;
                }

                if (file.size > 4 * 1024 * 1024) {
                    showError('Image size must be 4MB or less.');
                    resetSelection();

                    return;
                }

                selectedSourceFile = file;
                loadImageForCrop(file);
            });

            zoomRange.addEventListener('input', () => {
                if (!state.image) {
                    return;
                }

                const nextScale = Number.parseFloat(zoomRange.value);
                if (Number.isNaN(nextScale) || nextScale <= 0) {
                    return;
                }

                positionForScale(nextScale);
                updateZoomSliderVisuals();
            });

            cropCanvas.addEventListener('pointerdown', (event) => {
                if (!state.image) {
                    return;
                }

                state.dragActive = true;
                state.dragStartX = event.clientX - state.offsetX;
                state.dragStartY = event.clientY - state.offsetY;
                cropCanvas.setPointerCapture(event.pointerId);
            });

            cropCanvas.addEventListener('pointermove', (event) => {
                if (!state.dragActive || !state.image) {
                    return;
                }

                state.offsetX = event.clientX - state.dragStartX;
                state.offsetY = event.clientY - state.dragStartY;
                clampOffsets();
                renderCropCanvas();
            });

            window.addEventListener('pointerup', () => {
                state.dragActive = false;
            });

            cropCanvas.addEventListener('mouseleave', () => {
                state.dragActive = false;
            });

            cropApply.addEventListener('click', () => {
                if (!state.image || !selectedSourceFile) {
                    return;
                }

                const outputCanvas = document.createElement('canvas');
                outputCanvas.width = 640;
                outputCanvas.height = 640;
                const outputContext = outputCanvas.getContext('2d');
                if (!outputContext) {
                    showError('Unable to crop image right now. Please try again.');

                    return;
                }

                const exportScale = outputCanvas.width / cropCanvas.width;
                outputContext.imageSmoothingEnabled = true;
                outputContext.imageSmoothingQuality = 'high';
                outputContext.drawImage(
                    state.image,
                    state.offsetX * exportScale,
                    state.offsetY * exportScale,
                    state.image.width * state.scale * exportScale,
                    state.image.height * state.scale * exportScale
                );

                outputCanvas.toBlob((blob) => {
                    if (!blob) {
                        showError('Unable to generate cropped preview. Please try another image.');

                        return;
                    }

                    replaceAvatarFile(blob);
                    setModalOpen(false);
                    showError('');
                }, 'image/png', 0.92);
            });

            const cancelCrop = () => {
                showError('');
                resetSelection();
            };

            cropCancel.addEventListener('click', cancelCrop);
            cropClose.addEventListener('click', cancelCrop);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !cropModal.classList.contains('hidden')) {
                    cancelCrop();
                }
            });
        })();

        (() => {
            const passwordInput = document.getElementById('new_password');
            const currentPasswordInput = document.getElementById('current_password');
            const passwordConfirmationInput = document.getElementById('new_password_confirmation');
            const submitButton = document.getElementById('passwordUpdateSubmit');
            const ruleItems = document.querySelectorAll('[data-password-rule]');

            if (!passwordInput || !currentPasswordInput || !passwordConfirmationInput || !submitButton || ruleItems.length === 0) {
                return;
            }

            const rules = {
                length: (value) => value.length >= 8,
                upper: (value) => /[A-Z]/.test(value),
                lower: (value) => /[a-z]/.test(value),
                number: (value) => /\d/.test(value),
                special: (value) => /[^A-Za-z0-9]/.test(value),
            };

            const updatePasswordRuleState = () => {
                const value = passwordInput.value ?? '';
                let allRulesSatisfied = true;

                ruleItems.forEach((item) => {
                    const ruleName = item.getAttribute('data-password-rule') ?? '';
                    const isSatisfied = typeof rules[ruleName] === 'function' ? rules[ruleName](value) : false;
                    const passIcon = item.querySelector('[data-icon-pass]');
                    const failIcon = item.querySelector('[data-icon-fail]');

                    allRulesSatisfied = allRulesSatisfied && isSatisfied;

                    if (passIcon) {
                        passIcon.classList.toggle('hidden', !isSatisfied);
                    }

                    if (failIcon) {
                        failIcon.classList.toggle('hidden', isSatisfied);
                    }

                    item.classList.toggle('text-emerald-600', isSatisfied);
                    item.classList.toggle('text-red-600', !isSatisfied);
                });

                const hasCurrentPassword = (currentPasswordInput.value ?? '').length > 0;
                const hasMatchingConfirmation = (passwordConfirmationInput.value ?? '').length > 0
                    && (passwordConfirmationInput.value ?? '') === value;
                const canSubmit = hasCurrentPassword && allRulesSatisfied && hasMatchingConfirmation;

                submitButton.disabled = !canSubmit;
                submitButton.setAttribute('aria-disabled', canSubmit ? 'false' : 'true');
            };

            currentPasswordInput.addEventListener('input', updatePasswordRuleState);
            passwordInput.addEventListener('input', updatePasswordRuleState);
            passwordConfirmationInput.addEventListener('input', updatePasswordRuleState);
            updatePasswordRuleState();
        })();
    </script>
@endpush
