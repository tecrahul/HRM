@extends('layouts.dashboard-modern')

@section('title', 'Profile')
@section('page_heading', 'Profile')

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

        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
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
                    <label for="name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @error('name')
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
        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
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
        <article class="hrm-modern-surface rounded-2xl p-5 xl:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-extrabold">Two-Factor Authentication</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Add an extra verification step to secure your account.</p>
                </div>
                <span class="text-[11px] font-bold uppercase tracking-[0.1em] rounded-full px-2.5 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent); border: 1px solid var(--hr-line);">
                    {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            @if (session('two_factor_status'))
                <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #22c55e55; background: #22c55e12; color: #166534;">
                    {{ session('two_factor_status') }}
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
                    <div class="mt-4 rounded-xl border p-4" style="border-color: #f59e0b66; background: #fffbeb;">
                        <p class="text-sm font-semibold text-amber-700">New recovery codes generated</p>
                        <p class="text-xs mt-1 text-amber-700">Save these once-only codes now. They will not be shown again.</p>
                        <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs font-semibold">
                            @foreach($freshRecoveryCodes as $recoveryCode)
                                <code class="rounded-lg border px-2 py-1.5 text-center" style="border-color: #f59e0b66; background: #fff;">{{ $recoveryCode }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($twoFactorFeatureEnabled && $errors->twoFactorRecoveryCodes->any())
                    <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                        Recovery codes were not regenerated. Check password and try again.
                    </div>
                @endif

                @if ($twoFactorFeatureEnabled)
                    <form method="POST" action="{{ route('profile.two-factor.recovery-codes.regenerate') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                        @csrf
                        <div class="md:col-span-2">
                            <label for="two_factor_recovery_current_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Current Password</label>
                            <input id="two_factor_recovery_current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                            @error('current_password', 'twoFactorRecoveryCodes')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">Regenerate Recovery Codes</button>
                        </div>
                    </form>
                @endif

                @if ($errors->twoFactorDisable->any())
                    <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                        Could not disable two-factor authentication. Check password and retry.
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.two-factor.disable') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <div class="md:col-span-2">
                        <label for="two_factor_disable_current_password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Current Password</label>
                        <input id="two_factor_disable_current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        @error('current_password', 'twoFactorDisable')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-xl px-3.5 py-2 text-sm font-semibold text-red-700 border border-red-300 bg-red-50 hover:bg-red-100">Disable 2FA</button>
                    </div>
                </form>
            @else
                @if ($twoFactorFeatureEnabled)
                    <div class="mt-4 rounded-xl border p-4 space-y-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <p class="text-sm font-semibold">Setup Instructions</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Add a new account in your authenticator app using this secret key.</p>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Secret Key</p>
                            <code class="mt-1 inline-flex rounded-lg border px-2 py-1 text-xs font-semibold" style="border-color: var(--hr-line); background: #fff;">
                                {{ $twoFactorSetup['secret_formatted'] ?? 'Unavailable' }}
                            </code>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Provisioning URI</p>
                            <input type="text" readonly value="{{ $twoFactorSetup['otpauth_uri'] ?? '' }}" class="mt-1 w-full rounded-xl border px-3 py-2 text-xs bg-white" style="border-color: var(--hr-line);">
                        </div>
                    </div>

                    @if ($errors->twoFactorEnable->any())
                        <div class="mt-4 rounded-xl px-3 py-2 text-sm border" style="border-color: #ef444455; background: #ef444412; color: #991b1b;">
                            Could not enable two-factor authentication. Verify the code and password.
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
                            <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">Enable 2FA</button>
                        </div>
                    </form>
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

    <div id="avatarCropModal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4" style="background: rgb(2 8 23 / 0.72);">
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
                        <input id="avatarZoomRange" type="range" min="1" max="3" step="0.01" value="1" class="w-full">
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
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('profileAvatarPreview');
            const clientError = document.getElementById('avatarClientError');
            const cropModal = document.getElementById('avatarCropModal');
            const cropCanvas = document.getElementById('avatarCropCanvas');
            const zoomRange = document.getElementById('avatarZoomRange');
            const cropApply = document.getElementById('avatarCropApply');
            const cropCancel = document.getElementById('avatarCropCancel');
            const cropClose = document.getElementById('avatarCropClose');

            if (!avatarInput || !avatarPreview || !cropModal || !cropCanvas || !zoomRange || !cropApply || !cropCancel || !cropClose) {
                return;
            }

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
            };

            const resetSelection = () => {
                selectedSourceFile = null;
                state.image = null;
                state.dragActive = false;
                avatarInput.value = '';
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
