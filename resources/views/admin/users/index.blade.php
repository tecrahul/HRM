@extends('layouts.dashboard-modern')

@section('title', 'Users')
@section('page_heading', 'User Management')

@section('content')
    @if (session('status'))
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-emerald-600">{{ session('status') }}</p>
        </section>
    @endif

    @if (session('error'))
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">{{ session('error') }}</p>
        </section>
    @endif

    <style>
        /* Info Card Theme Variables */
        .info-card-blue {
            --card-bg: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            --card-border: rgba(59, 130, 246, 0.2);
            --card-accent: radial-gradient(circle, #3b82f6 0%, transparent 70%);
            --card-title: #1e40af;
            --card-value: #1e3a8a;
            --card-subtitle: #3b82f6;
            --card-icon-bg: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .info-card-purple {
            --card-bg: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            --card-border: rgba(139, 92, 246, 0.2);
            --card-accent: radial-gradient(circle, #8b5cf6 0%, transparent 70%);
            --card-title: #5b21b6;
            --card-value: #4c1d95;
            --card-subtitle: #7c3aed;
            --card-icon-bg: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .info-card-rose {
            --card-bg: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
            --card-border: rgba(236, 72, 153, 0.2);
            --card-accent: radial-gradient(circle, #ec4899 0%, transparent 70%);
            --card-title: #9d174d;
            --card-value: #831843;
            --card-subtitle: #db2777;
            --card-icon-bg: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        }
        .info-card-emerald {
            --card-bg: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            --card-border: rgba(16, 185, 129, 0.2);
            --card-accent: radial-gradient(circle, #10b981 0%, transparent 70%);
            --card-title: #047857;
            --card-value: #064e3b;
            --card-subtitle: #059669;
            --card-icon-bg: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        /* Dark Mode Overrides */
        .dark .info-card-blue {
            --card-bg: linear-gradient(135deg, rgba(30, 58, 138, 0.3) 0%, rgba(30, 64, 175, 0.2) 100%);
            --card-border: rgba(59, 130, 246, 0.3);
            --card-accent: radial-gradient(circle, rgba(59, 130, 246, 0.4) 0%, transparent 70%);
            --card-title: #93c5fd;
            --card-value: #bfdbfe;
            --card-subtitle: #60a5fa;
        }
        .dark .info-card-purple {
            --card-bg: linear-gradient(135deg, rgba(76, 29, 149, 0.3) 0%, rgba(91, 33, 182, 0.2) 100%);
            --card-border: rgba(139, 92, 246, 0.3);
            --card-accent: radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, transparent 70%);
            --card-title: #c4b5fd;
            --card-value: #ddd6fe;
            --card-subtitle: #a78bfa;
        }
        .dark .info-card-rose {
            --card-bg: linear-gradient(135deg, rgba(136, 19, 55, 0.3) 0%, rgba(159, 18, 57, 0.2) 100%);
            --card-border: rgba(236, 72, 153, 0.3);
            --card-accent: radial-gradient(circle, rgba(236, 72, 153, 0.4) 0%, transparent 70%);
            --card-title: #fbcfe8;
            --card-value: #fce7f3;
            --card-subtitle: #f9a8d4;
        }
        .dark .info-card-emerald {
            --card-bg: linear-gradient(135deg, rgba(6, 78, 59, 0.3) 0%, rgba(4, 120, 87, 0.2) 100%);
            --card-border: rgba(16, 185, 129, 0.3);
            --card-accent: radial-gradient(circle, rgba(16, 185, 129, 0.4) 0%, transparent 70%);
            --card-title: #6ee7b7;
            --card-value: #a7f3d0;
            --card-subtitle: #34d399;
        }

        .info-card {
            background: var(--card-bg);
            border-color: var(--card-border);
        }
        .info-card .card-accent {
            background: var(--card-accent);
        }
        .info-card .card-title {
            color: var(--card-title);
        }
        .info-card .card-value {
            color: var(--card-value);
        }
        .info-card .card-subtitle {
            color: var(--card-subtitle);
        }
        .info-card .card-icon {
            background: var(--card-icon-bg);
        }
    </style>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Total Users Card - Blue Theme -->
        <article class="info-card info-card-blue relative overflow-hidden rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg">
            <div class="card-accent absolute top-0 right-0 w-24 h-24 opacity-10" style="transform: translate(30%, -30%);"></div>
            <div class="flex items-start justify-between gap-3 relative z-10">
                <div>
                    <p class="card-title text-xs uppercase tracking-[0.12em] font-bold">Total Users</p>
                    <p class="card-value mt-3 text-4xl font-black">{{ $stats['total'] }}</p>
                    <p class="card-subtitle mt-1 text-xs font-medium">All registered accounts</p>
                </div>
                <span class="card-icon h-12 w-12 rounded-xl flex items-center justify-center shadow-sm text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>

        <!-- Admins Card - Purple Theme -->
        <article class="info-card info-card-purple relative overflow-hidden rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg">
            <div class="card-accent absolute top-0 right-0 w-24 h-24 opacity-10" style="transform: translate(30%, -30%);"></div>
            <div class="flex items-start justify-between gap-3 relative z-10">
                <div>
                    <p class="card-title text-xs uppercase tracking-[0.12em] font-bold">Admins</p>
                    <p class="card-value mt-3 text-4xl font-black">{{ $stats['admins'] }}</p>
                    <p class="card-subtitle mt-1 text-xs font-medium">System administrators</p>
                </div>
                <span class="card-icon h-12 w-12 rounded-xl flex items-center justify-center shadow-sm text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </span>
            </div>
        </article>

        <!-- HR Card - Rose/Pink Theme -->
        <article class="info-card info-card-rose relative overflow-hidden rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg">
            <div class="card-accent absolute top-0 right-0 w-24 h-24 opacity-10" style="transform: translate(30%, -30%);"></div>
            <div class="flex items-start justify-between gap-3 relative z-10">
                <div>
                    <p class="card-title text-xs uppercase tracking-[0.12em] font-bold">HR Managers</p>
                    <p class="card-value mt-3 text-4xl font-black">{{ $stats['hr'] }}</p>
                    <p class="card-subtitle mt-1 text-xs font-medium">Human resources team</p>
                </div>
                <span class="card-icon h-12 w-12 rounded-xl flex items-center justify-center shadow-sm text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </span>
            </div>
        </article>

        <!-- Employees Card - Emerald/Green Theme -->
        <article class="info-card info-card-emerald relative overflow-hidden rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg">
            <div class="card-accent absolute top-0 right-0 w-24 h-24 opacity-10" style="transform: translate(30%, -30%);"></div>
            <div class="flex items-start justify-between gap-3 relative z-10">
                <div>
                    <p class="card-title text-xs uppercase tracking-[0.12em] font-bold">Employees</p>
                    <p class="card-value mt-3 text-4xl font-black">{{ $stats['employees'] }}</p>
                    <p class="card-subtitle mt-1 text-xs font-medium">Active workforce</p>
                </div>
                <span class="card-icon h-12 w-12 rounded-xl flex items-center justify-center shadow-sm text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="hrm-modern-surface rounded-2xl p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Users</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Create and manage platform users and profile metadata.</p>
                </div>
            </div>
            <a href="{{ route('admin.users.create') }}" class="ui-btn ui-btn-primary">
                <x-heroicon-o-plus class="h-4 w-4" />
                Add User
            </a>
        </div>

        <form id="usersFilterForm" method="GET" action="{{ route('admin.users.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4">
            <input type="hidden" name="page" value="{{ request('page', method_exists($users, 'currentPage') ? $users->currentPage() : 1) }}" />
            <input type="hidden" name="branch" id="usersFilterBranch" value="{{ $filters['branch'] }}" />
            <input type="hidden" name="department" id="usersFilterDepartment" value="{{ $filters['department'] }}" />
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search name, email, department, branch..." class="md:col-span-4 rounded-xl border px-3 h-10 bg-transparent text-[13px] md:text-[14px] placeholder:text-[var(--hr-text-muted)]" style="border-color: var(--hr-line);" />
            <select name="role" class="md:col-span-2 rounded-xl border px-3 h-10 bg-transparent text-[13px] md:text-[14px]" style="border-color: var(--hr-line);">
                <option value="">All Roles</option>
                @foreach($roleOptions as $roleOption)
                    <option value="{{ $roleOption->value }}" {{ $filters['role'] === $roleOption->value ? 'selected' : '' }}>{{ $roleOption->label() }}</option>
                @endforeach
            </select>
            <div class="md:col-span-3 flex items-center gap-2">
                <select name="sort_by" class="w-full rounded-xl border px-3 h-10 bg-transparent text-[13px] md:text-[14px]" style="border-color: var(--hr-line);">
                    <option value="">Sort: Default</option>
                    <option value="first_name" {{ (($filters['sort_by'] ?? '') === 'first_name') ? 'selected' : '' }}>First Name</option>
                    <option value="last_name" {{ (($filters['sort_by'] ?? '') === 'last_name') ? 'selected' : '' }}>Last Name</option>
                    <option value="full_name" {{ (($filters['sort_by'] ?? '') === 'full_name') ? 'selected' : '' }}>Full Name</option>
                </select>
                <select name="sort_dir" class="w-full rounded-xl border px-3 h-10 bg-transparent text-[13px] md:text-[14px]" style="border-color: var(--hr-line);">
                    <option value="asc" {{ (($filters['sort_dir'] ?? 'desc') === 'asc') ? 'selected' : '' }}>Asc</option>
                    <option value="desc" {{ (($filters['sort_dir'] ?? 'desc') === 'desc') ? 'selected' : '' }}>Desc</option>
                </select>
            </div>
            <div class="md:col-span-3 flex items-center gap-2">
                <select name="status" class="w-full rounded-xl border px-3 h-10 bg-transparent text-[13px] md:text-[14px]" style="border-color: var(--hr-line);">
                    <option value="">All Status</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
                <button id="usersFilterButton" type="submit" class="rounded-xl px-3 h-10 text-[13px] md:text-[14px] font-medium border opacity-80 hover:opacity-100" style="border-color: var(--hr-line); display:inline-flex; align-items:center; gap:6px; color: var(--hr-text-muted);">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                    Filter
                </button>
                @if(request('q') || request('role') || request('status') || request('sort_by') || request('sort_dir'))
                    <a href="{{ route('admin.users.index', array_filter(['branch' => $filters['branch'], 'department' => $filters['department']])) }}" class="text-xs md:text-sm font-medium underline-offset-2 hover:underline" style="color: var(--hr-text-muted);">Clear</a>
                @endif
                <span id="usersFilterLoading" class="ml-1 hidden" aria-live="polite" aria-label="Loading">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25" />
                        <path d="M12 2a10 10 0 0 1 10 10" />
                    </svg>
                </span>
            </div>
        </form>

        @push('scripts')
        <script>
            (function(){
                const form = document.getElementById('usersFilterForm');
                if (!form) return;
                const q = form.querySelector('input[name="q"]');
                const role = form.querySelector('select[name="role"]');
                const status = form.querySelector('select[name="status"]');
                const sortBy = form.querySelector('select[name="sort_by"]');
                const sortDir = form.querySelector('select[name="sort_dir"]');
                const page = form.querySelector('input[name="page"]');
                const loading = document.getElementById('usersFilterLoading');
                const filterBtn = document.getElementById('usersFilterButton');

                const showLoading = () => {
                    if (loading) loading.classList.remove('hidden');
                    if (filterBtn) filterBtn.setAttribute('aria-busy', 'true');
                };

                const hideLoading = () => {
                    if (loading) loading.classList.add('hidden');
                    if (filterBtn) filterBtn.removeAttribute('aria-busy');
                };

                const debounce = (fn, delay = 450) => {
                    let t;
                    return (...args) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn.apply(null, args), delay);
                    };
                };

                const submitForm = (preservePage = true) => {
                    try {
                        if (preservePage) {
                            // Keep current page if present; fallback to 1
                            const params = new URLSearchParams(window.location.search);
                            const current = params.get('page') || (page && page.value) || '1';
                            if (page) page.value = current;
                        } else {
                            if (page) page.value = '1';
                        }
                    } catch (e) {
                        // Best effort; ignore
                    }
                    showLoading();
                    form.submit();
                };

                // Automatic apply when typing in search with debounce (400-500ms)
                if (q) {
                    const debounced = debounce(() => submitForm(true), 450);
                    q.addEventListener('input', debounced);
                }

                // Immediate apply on select changes
                [role, status, sortBy, sortDir].forEach(el => {
                    if (!el) return;
                    el.addEventListener('change', () => submitForm(true));
                });

                // Show loading on manual submit as well
                form.addEventListener('submit', () => {
                    showLoading();
                });

                // Hide loading if navigation prevented (unlikely here)
                window.addEventListener('pageshow', () => hideLoading());

                // --- Global Filters sync ---
                const branchInput = document.getElementById('usersFilterBranch');
                const departmentInput = document.getElementById('usersFilterDepartment');

                const applyGlobalFilters = (gf, resubmit) => {
                    const branch = (gf && gf.branch) ? gf.branch : '';
                    const department = (gf && gf.department) ? gf.department : '';
                    if (branchInput) branchInput.value = branch;
                    if (departmentInput) departmentInput.value = department;
                    if (resubmit) {
                        if (page) page.value = '1';
                        showLoading();
                        form.submit();
                    }
                };

                // On page load: read localStorage and auto-submit if stored filters differ from current URL params
                (function syncOnLoad() {
                    try {
                        const stored = JSON.parse(localStorage.getItem('hrm-global-filters') || '{}');
                        const urlParams = new URLSearchParams(window.location.search);
                        const urlBranch = urlParams.get('branch') || '';
                        const urlDept   = urlParams.get('department') || '';
                        const storedBranch = stored.branch || '';
                        const storedDept   = stored.department || '';
                        if (storedBranch !== urlBranch || storedDept !== urlDept) {
                            applyGlobalFilters(stored, true);
                        }
                    } catch (e) {
                        // ignore
                    }
                })();

                // On global filter change event: re-submit form with new branch/department
                window.addEventListener('globalFiltersChanged', (e) => {
                    applyGlobalFilters(e.detail, true);
                });
            })();
        </script>
        @endpush

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[840px] text-sm">
                <thead>
                <tr class="border-b text-left" style="border-color: var(--hr-line); color: var(--hr-text-muted);">
                    <th class="py-2.5 px-2 font-semibold">User</th>
                    <th class="py-2.5 px-2 font-semibold">Role</th>
                    <th class="py-2.5 px-2 font-semibold">Department</th>
                    <th class="py-2.5 px-2 font-semibold">Status</th>
                    <th class="py-2.5 px-2 font-semibold">Joined</th>
                    <th class="py-2.5 px-2 font-semibold text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @if($users && $users->count())
                    @foreach($users as $managedUser)
                    <tr class="border-b" style="border-color: var(--hr-line);">
                        <td class="py-3 px-2">
                            <p class="font-semibold">{{ $managedUser->full_name }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $managedUser->email }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                                {{ $managedUser->role instanceof \App\Enums\UserRole ? $managedUser->role->label() : ucfirst((string) $managedUser->role) }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold">{{ $managedUser->profile?->department ?? 'N/A' }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $managedUser->profile?->branch ?? 'No branch' }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="{{ ($managedUser->profile?->status === 'inactive') ? 'color:#b45309;background:rgb(245 158 11 / 0.18);' : (($managedUser->profile?->status === 'suspended') ? 'color:#b91c1c;background:rgb(239 68 68 / 0.18);' : 'color:#15803d;background:rgb(34 197 94 / 0.16);') }}">
                                {{ ucfirst($managedUser->profile?->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="py-3 px-2">{{ $managedUser->profile?->joined_on ? $managedUser->profile->joined_on->format('M d, Y') : 'N/A' }}</td>
                        <td class="py-3 px-2">
                            <div class="flex justify-end items-center gap-2">
                                @can('update', $managedUser)
                                    <a href="{{ route('admin.users.edit', $managedUser) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">Edit</a>
                                @endcan
                                @can('delete', $managedUser)
                                    <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600" style="border-color: rgb(239 68 68 / 0.45);">Delete</button>
                                    </form>
                                @endcan
                                @cannot('update', $managedUser)
                                    @cannot('delete', $managedUser)
                                        <span class="text-xs text-gray-500 italic">No actions</span>
                                    @endcannot
                                @endcannot
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="py-6 text-center text-sm" style="color: var(--hr-text-muted);">No users found for the selected filters.</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </section>
@endsection
