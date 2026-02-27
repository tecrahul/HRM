@php
    $managedUser = $managedUser ?? null;
    $isEdit = isset($managedUser);
    $profile = $managedUser?->profile;
    $departmentOptions = $departmentOptions ?? [];
    $branchOptions = $branchOptions ?? [];
    $supervisorOptions = $supervisorOptions ?? collect();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" data-inline-validation>
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <section class="hrm-modern-surface rounded-2xl p-5">
        <div class="flex items-center gap-2">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg>
            </span>
            <div>
                <h3 class="text-lg font-extrabold">Account</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Primary access details and role assignment.</p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">First Name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $managedUser->first_name ?? '') }}" maxlength="120" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('first_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="last_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $managedUser->last_name ?? '') }}" maxlength="120" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('last_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="middle_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Middle Name (Optional)</label>
                <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $managedUser->middle_name ?? '') }}" maxlength="120" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('middle_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" maxlength="255" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="role" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Role</label>
                <select id="role" name="role" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $managedRole = $managedUser->role ?? null;
                        $currentRole = old('role', $managedRole instanceof \App\Enums\UserRole ? $managedRole->value : ((string) $managedRole ?: \App\Enums\UserRole::EMPLOYEE->value));
                    @endphp
                    @foreach($roleOptions as $roleOption)
                        <option value="{{ $roleOption->value }}" {{ $currentRole === $roleOption->value ? 'selected' : '' }}>{{ $roleOption->label() }}</option>
                    @endforeach
                </select>
                @error('role')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Status</label>
                <select id="status" name="status" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $currentStatus = old('status', $profile?->status ?? 'active');
                    @endphp
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ $currentStatus === $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">
                    Password {{ $isEdit ? '(Leave blank to keep current)' : '' }}
                </label>
                <div class="relative">
                    <input id="password" name="password" type="password" minlength="8" @if(! $isEdit) required @endif class="w-full rounded-xl border px-3 py-2.5 pr-10 bg-transparent" style="border-color: var(--hr-line);">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100/60 hover:text-slate-700" data-password-toggle data-target="password" data-visible="false" aria-label="Show password">
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
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Confirm Password</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" @if(! $isEdit) required @endif class="w-full rounded-xl border px-3 py-2.5 pr-10 bg-transparent" style="border-color: var(--hr-line);">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100/60 hover:text-slate-700" data-password-toggle data-target="password_confirmation" data-visible="false" aria-label="Show password">
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
        </div>
    </section>

    <section class="hrm-modern-surface rounded-2xl p-5">
        <div class="flex items-center gap-2">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
            </span>
            <div>
                <h3 class="text-lg font-extrabold">Profile</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Employment and contact metadata for HR operations.</p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Phone</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $profile?->phone) }}" maxlength="40" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('phone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="alternate_phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Alternate Phone</label>
                <input id="alternate_phone" name="alternate_phone" type="text" value="{{ old('alternate_phone', $profile?->alternate_phone) }}" maxlength="40" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('alternate_phone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="designation_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Designation</label>
                @php
                    $currentDesignationId = (string) old('designation_id', isset($managedUser?->designation_id) ? (string) $managedUser->designation_id : '');
                @endphp
                <select id="designation_id" name="designation_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select designation</option>
                    @foreach(($designationOptions ?? []) as $opt)
                        <option value="{{ $opt['id'] }}" {{ $currentDesignationId === (string) $opt['id'] ? 'selected' : '' }}>
                            {{ !empty($opt['code']) ? ($opt['name'].' ('.$opt['code'].')') : $opt['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('designation_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="department" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Department</label>
                @php
                    $currentDepartment = old('department', $profile?->department);
                @endphp
                <select id="department" name="department" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select department</option>
                    @foreach($departmentOptions as $departmentOption)
                        <option value="{{ $departmentOption }}" {{ $currentDepartment === $departmentOption ? 'selected' : '' }}>{{ $departmentOption }}</option>
                    @endforeach
                    @if(! blank($currentDepartment) && ! in_array($currentDepartment, $departmentOptions, true))
                        <option value="{{ $currentDepartment }}" selected>{{ $currentDepartment }}</option>
                    @endif
                </select>
                @error('department')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="branch" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Branch</label>
                @php
                    $currentBranch = old('branch', $profile?->branch);
                @endphp
                <select id="branch" name="branch" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select branch</option>
                    @foreach($branchOptions as $branchOption)
                        <option value="{{ $branchOption }}" {{ $currentBranch === $branchOption ? 'selected' : '' }}>{{ $branchOption }}</option>
                    @endforeach
                    @if(! blank($currentBranch) && ! in_array($currentBranch, $branchOptions, true))
                        <option value="{{ $currentBranch }}" selected>{{ $currentBranch }}</option>
                    @endif
                </select>
                @error('branch')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="job_title" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Job Title</label>
                <input id="job_title" name="job_title" type="text" value="{{ old('job_title', $profile?->job_title) }}" maxlength="100" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('job_title')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="supervisor_user_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Direct Supervisor</label>
                @php
                    $currentSupervisorId = (string) old('supervisor_user_id', $profile?->supervisor_user_id ? (string) $profile?->supervisor_user_id : '');
                @endphp
                <select id="supervisor_user_id" name="supervisor_user_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select supervisor</option>
                    @foreach($supervisorOptions as $supervisorOption)
                        @php
                            $supervisorRole = $supervisorOption->role instanceof \App\Enums\UserRole
                                ? $supervisorOption->role->label()
                                : ucfirst((string) $supervisorOption->role);
                        @endphp
                        <option value="{{ $supervisorOption->id }}" {{ $currentSupervisorId === (string) $supervisorOption->id ? 'selected' : '' }}>
                            {{ $supervisorOption->name }} ({{ $supervisorRole }})
                        </option>
                    @endforeach
                </select>
                @error('supervisor_user_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="manager_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Manager Name (Optional)</label>
                <input id="manager_name" name="manager_name" type="text" value="{{ old('manager_name', $profile?->manager_name) }}" maxlength="120" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Leave blank to auto-fill from selected supervisor.</p>
                @error('manager_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="employment_type" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Employment Type</label>
                <select id="employment_type" name="employment_type" required class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    @php
                        $currentType = old('employment_type', $profile?->employment_type ?? 'full_time');
                    @endphp
                    @foreach($employmentTypes as $employmentType)
                        <option value="{{ $employmentType }}" {{ $currentType === $employmentType ? 'selected' : '' }}>
                            {{ str($employmentType)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
                @error('employment_type')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="joined_on" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Joined On</label>
                <input id="joined_on" name="joined_on" type="date" value="{{ old('joined_on', $profile?->joined_on?->format('Y-m-d')) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('joined_on')
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
                    $currentGender = old('gender', $profile?->gender);
                @endphp
                <select id="gender" name="gender" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select gender</option>
                    <option value="male" {{ $currentGender === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $currentGender === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ $currentGender === 'other' ? 'selected' : '' }}>Other</option>
                    <option value="prefer_not_to_say" {{ $currentGender === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                </select>
                @error('gender')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="marital_status" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Marital Status</label>
                @php
                    $currentMaritalStatus = old('marital_status', $profile?->marital_status);
                @endphp
                <select id="marital_status" name="marital_status" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">Select marital status</option>
                    <option value="single" {{ $currentMaritalStatus === 'single' ? 'selected' : '' }}>Single</option>
                    <option value="married" {{ $currentMaritalStatus === 'married' ? 'selected' : '' }}>Married</option>
                    <option value="divorced" {{ $currentMaritalStatus === 'divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="widowed" {{ $currentMaritalStatus === 'widowed' ? 'selected' : '' }}>Widowed</option>
                    <option value="prefer_not_to_say" {{ $currentMaritalStatus === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                </select>
                @error('marital_status')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="nationality" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Nationality</label>
                <input id="nationality" name="nationality" type="text" value="{{ old('nationality', $profile?->nationality) }}" maxlength="80" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('nationality')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="national_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">National ID / Passport</label>
                <input id="national_id" name="national_id" type="text" value="{{ old('national_id', $profile?->national_id) }}" maxlength="80" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('national_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="work_location" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Work Location</label>
                <input id="work_location" name="work_location" type="text" value="{{ old('work_location', $profile?->work_location) }}" maxlength="120" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('work_location')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="emergency_contact_name" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Emergency Contact Name</label>
                <input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $profile?->emergency_contact_name) }}" maxlength="120" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('emergency_contact_name')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="emergency_contact_phone" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Emergency Contact Phone</label>
                <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" value="{{ old('emergency_contact_phone', $profile?->emergency_contact_phone) }}" maxlength="40" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('emergency_contact_phone')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label for="linkedin_url" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">LinkedIn URL</label>
                <input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $profile?->linkedin_url) }}" maxlength="255" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                @error('linkedin_url')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label for="address" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Address</label>
                <textarea id="address" name="address" rows="3" maxlength="1000" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('address', $profile?->address) }}</textarea>
                @error('address')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" class="ui-btn ui-btn-primary">
            <x-heroicon-o-check class="h-4 w-4" />
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold border inline-flex items-center gap-2" style="border-color: var(--hr-line);">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
            Cancel
        </a>
    </div>
</form>
