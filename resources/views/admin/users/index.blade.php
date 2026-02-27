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

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Total Users</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['total'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-3-3.87"></path><path d="M7 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Admins</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['admins'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(124 58 237 / 0.16); color: #7c3aed;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l2.5 4.5L19 8l-3.5 3 1 4.5-4.5-2.5L7.5 15.5l1-4.5L5 8l4.5-.5L12 3z"></path></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">HR</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['hr'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(236 72 153 / 0.16); color: #db2777;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path></svg>
                </span>
            </div>
        </article>
        <article class="hrm-modern-surface rounded-2xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Employees</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['employees'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
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
                @if(request()->hasAny(['q','role','status','sort_by','sort_dir','page']) && (request('q') || request('role') || request('status') || request('sort_by') || request('sort_dir')))
                    <a href="{{ route('admin.users.index') }}" class="text-xs md:text-sm font-medium underline-offset-2 hover:underline" style="color: var(--hr-text-muted);">Clear</a>
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
                                <a href="{{ route('admin.users.edit', $managedUser) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">Edit</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" onsubmit="return confirm('Delete this user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600" style="border-color: rgb(239 68 68 / 0.45);">Delete</button>
                                </form>
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
