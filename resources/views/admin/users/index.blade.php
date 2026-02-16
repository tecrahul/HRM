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
            <a href="{{ route('admin.users.create') }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white inline-flex items-center gap-2" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                Add User
            </a>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search name, email, department, branch..." class="md:col-span-2 rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
            <select name="role" class="rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                <option value="">All Roles</option>
                @foreach($roleOptions as $roleOption)
                    <option value="{{ $roleOption->value }}" {{ $filters['role'] === $roleOption->value ? 'selected' : '' }}>{{ $roleOption->label() }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <select name="status" class="flex-1 rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                    <option value="">All Status</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl px-3 py-2.5 text-sm font-semibold border" style="border-color: var(--hr-line);">Filter</button>
            </div>
        </form>

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
                @forelse($users as $managedUser)
                    @php
                        $profile = $managedUser->profile;
                        $status = $profile?->status ?? 'active';
                        $statusStyles = match ($status) {
                            'inactive' => 'color:#b45309;background:rgb(245 158 11 / 0.18);',
                            'suspended' => 'color:#b91c1c;background:rgb(239 68 68 / 0.18);',
                            default => 'color:#15803d;background:rgb(34 197 94 / 0.16);',
                        };
                    @endphp
                    <tr class="border-b" style="border-color: var(--hr-line);">
                        <td class="py-3 px-2">
                            <p class="font-semibold">{{ $managedUser->name }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $managedUser->email }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                                {{ $managedUser->role instanceof \App\Enums\UserRole ? $managedUser->role->label() : ucfirst((string) $managedUser->role) }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold">{{ $profile?->department ?? 'N/A' }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $profile?->branch ?? 'No branch' }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <span class="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="{{ $statusStyles }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="py-3 px-2">{{ $profile?->joined_on?->format('M d, Y') ?? 'N/A' }}</td>
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
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-sm" style="color: var(--hr-text-muted);">No users found for the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </section>
@endsection
