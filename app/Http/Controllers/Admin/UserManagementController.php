<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * @return list<string>
     */
    private function profileStatuses(): array
    {
        return ['active', 'inactive', 'suspended'];
    }

    /**
     * @return list<string>
     */
    private function employmentTypes(): array
    {
        return ['full_time', 'part_time', 'contract'];
    }

    /**
     * @return list<string>
     */
    private function managerOptions(?int $excludeUserId = null): array
    {
        return User::query()
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::HR->value])
            ->when($excludeUserId !== null, function ($query) use ($excludeUserId): void {
                $query->whereKeyNot($excludeUserId);
            })
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name): bool => ! blank($name))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function departmentOptions(): array
    {
        $departmentNames = UserProfile::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->all();

        $managedDepartments = Department::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->pluck('name')
            ->all();

        return collect($departmentNames)
            ->merge($managedDepartments)
            ->filter(fn ($department): bool => ! blank($department))
            ->map(fn ($department): string => trim((string) $department))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function branchOptions(): array
    {
        $profileBranches = UserProfile::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->pluck('branch')
            ->all();

        $managedBranches = Branch::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->pluck('name')
            ->all();

        return collect($profileBranches)
            ->merge($managedBranches)
            ->filter(fn ($branch): bool => ! blank($branch))
            ->map(fn ($branch): string => trim((string) $branch))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function index(Request $request): View
    {
        $search = (string) $request->string('q');
        $role = (string) $request->string('role');
        $status = (string) $request->string('status');

        $users = User::query()
            ->with('profile')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('profile', function ($profileQuery) use ($search): void {
                            $profileQuery
                                ->where('department', 'like', "%{$search}%")
                                ->orWhere('branch', 'like', "%{$search}%")
                                ->orWhere('job_title', 'like', "%{$search}%")
                                ->orWhere('manager_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when($role !== '' && in_array($role, UserRole::values(), true), function ($query) use ($role): void {
                $query->where('role', $role);
            })
            ->when($status !== '' && in_array($status, $this->profileStatuses(), true), function ($query) use ($status): void {
                $query->whereHas('profile', function ($profileQuery) use ($status): void {
                    $profileQuery->where('status', $status);
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'q' => $search,
                'role' => $role,
                'status' => $status,
            ],
            'roleOptions' => UserRole::cases(),
            'statusOptions' => $this->profileStatuses(),
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', UserRole::ADMIN->value)->count(),
                'hr' => User::query()->where('role', UserRole::HR->value)->count(),
                'employees' => User::query()->where('role', UserRole::EMPLOYEE->value)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roleOptions' => UserRole::cases(),
            'statusOptions' => $this->profileStatuses(),
            'employmentTypes' => $this->employmentTypes(),
            'departmentOptions' => $this->departmentOptions(),
            'branchOptions' => $this->branchOptions(),
            'managerOptions' => $this->managerOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $viewer = $request->user();

        $createdUser = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'],
            ]);

            $user->profile()->create($this->extractProfilePayload($validated));
            $user->loadMissing('profile');

            return $user;
        });

        ActivityLogger::log(
            $viewer,
            'user.created',
            'User account created',
            "{$createdUser->name} ({$createdUser->email})",
            '#7c3aed',
            $createdUser,
            ['role' => (string) $createdUser->role]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $user->load('profile');

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roleOptions' => UserRole::cases(),
            'statusOptions' => $this->profileStatuses(),
            'employmentTypes' => $this->employmentTypes(),
            'departmentOptions' => $this->departmentOptions(),
            'branchOptions' => $this->branchOptions(),
            'managerOptions' => $this->managerOptions($user->id),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatePayload($request, $user);
        $viewer = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $userPayload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = $validated['password'];
            }

            $user->update($userPayload);
            $user->profile()->updateOrCreate([], $this->extractProfilePayload($validated));
        });

        ActivityLogger::log(
            $viewer,
            'user.updated',
            'User account updated',
            "{$user->name} ({$user->email})",
            '#ec4899',
            $user,
            ['role' => (string) $user->role]
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();

        if ((int) $request->user()?->id === (int) $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole(UserRole::ADMIN) && User::query()->where('role', UserRole::ADMIN->value)->count() <= 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'At least one admin account must remain.');
        }

        $meta = "{$user->name} ({$user->email})";
        $userId = $user->id;
        $user->delete();

        ActivityLogger::log(
            $viewer,
            'user.deleted',
            'User account deleted',
            $meta,
            '#ef4444',
            $userId
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?User $user = null): array
    {
        $userId = $user?->id;
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(UserRole::values())],
            'password' => $passwordRules,

            'phone' => ['nullable', 'string', 'max:40'],
            'alternate_phone' => ['nullable', 'string', 'max:40'],
            'department' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', Rule::in($this->employmentTypes())],
            'status' => ['required', Rule::in($this->profileStatuses())],
            'joined_on' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'prefer_not_to_say'])],
            'nationality' => ['nullable', 'string', 'max:80'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'work_location' => ['nullable', 'string', 'max:120'],
            'manager_name' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function extractProfilePayload(array $validated): array
    {
        return [
            'phone' => $validated['phone'] ?: null,
            'alternate_phone' => $validated['alternate_phone'] ?: null,
            'department' => $validated['department'] ?: null,
            'branch' => $validated['branch'] ?: null,
            'job_title' => $validated['job_title'] ?: null,
            'employment_type' => $validated['employment_type'],
            'status' => $validated['status'],
            'joined_on' => $validated['joined_on'] ?: null,
            'date_of_birth' => $validated['date_of_birth'] ?: null,
            'gender' => $validated['gender'] ?: null,
            'marital_status' => $validated['marital_status'] ?: null,
            'nationality' => $validated['nationality'] ?: null,
            'national_id' => $validated['national_id'] ?: null,
            'work_location' => $validated['work_location'] ?: null,
            'manager_name' => $validated['manager_name'] ?: null,
            'linkedin_url' => $validated['linkedin_url'] ?: null,
            'address' => $validated['address'] ?: null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?: null,
        ];
    }
}
