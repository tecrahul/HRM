@extends('layouts.dashboard-modern')

@section('title', 'Profile')
@section('page_heading', 'Profile')

@section('content')
    @php
        $user = auth()->user();
        $profile = $user?->profile;
        $role = $user?->role;
        $roleLabel = $role instanceof \App\Enums\UserRole ? $role->label() : ucfirst((string) $role);
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
                    <img src="{{ $avatarUrl }}" alt="Profile avatar" class="h-28 w-28 rounded-2xl object-cover border" style="border-color: var(--hr-line);">
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
@endsection
