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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\Designation;

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

    private function supervisorOptions(?int $excludeUserId = null): EloquentCollection
    {
        return User::query()
            ->when($excludeUserId !== null, function ($query) use ($excludeUserId): void {
                $query->whereKeyNot($excludeUserId);
            })
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    /**
     * @return list<array{id:int,name:string,code:?string}>
     */
    private function designationOptions(): array
    {
        return Designation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(static fn (Designation $d): array => [
                'id' => (int) $d->id,
                'name' => (string) $d->name,
                'code' => $d->code !== null && trim((string) $d->code) !== '' ? (string) $d->code : null,
            ])
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
        $sortBy = (string) $request->string('sort_by');
        $sortDir = strtolower((string) $request->string('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->with('profile')
            ->when($search !== '', function ($query) use ($search): void {
                $safeLike = "%".str_replace(['\\\\', '%', '_'], ['\\\\\\\\', '\\%', '\\_'], $search)."%";
                $query->where(function ($innerQuery) use ($safeLike): void {
                    $innerQuery
                        ->where('first_name', 'like', $safeLike)
                        ->orWhere('last_name', 'like', $safeLike)
                        ->orWhereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?", [$safeLike])
                        ->orWhere('email', 'like', $safeLike)
                        ->orWhereHas('profile', function ($profileQuery) use ($safeLike): void {
                            $profileQuery
                                ->where('department', 'like', $safeLike)
                                ->orWhere('branch', 'like', $safeLike)
                                ->orWhere('job_title', 'like', $safeLike)
                                ->orWhere('manager_name', 'like', $safeLike)
                                ->orWhere('phone', 'like', $safeLike);
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
            ->when(in_array($sortBy, ['first_name', 'last_name', 'full_name'], true), function ($query) use ($sortBy, $sortDir): void {
                if ($sortBy === 'full_name') {
                    $query->orderByRaw("CONCAT_WS(' ', first_name, middle_name, last_name) {$sortDir}");
                } else {
                    $query->orderBy($sortBy, $sortDir);
                }
            }, function ($query): void {
                $query->orderByDesc('id');
            })
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'q' => $search,
                'role' => $role,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'roleOptions' => UserRole::cases(),
            'statusOptions' => $this->profileStatuses(),
            'stats' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', UserRole::ADMIN->value)->count(),
                'hr' => User::query()->where('role', UserRole::HR->value)->count(),
                'employees' => User::query()->workforce()->count(),
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
            'supervisorOptions' => $this->supervisorOptions(),
            'designationOptions' => $this->designationOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $viewer = $request->user();

        $createdUser = DB::transaction(function () use ($validated): User {
            $first = trim((string) $validated['first_name']);
            $middle = ($validated['middle_name'] ?? null) ? trim((string) $validated['middle_name']) : null;
            $last = trim((string) $validated['last_name']);
            $legacyFull = trim(implode(' ', array_values(array_filter([$first, $middle, $last], fn($p) => (string)$p !== ''))));

            $user = User::query()->create([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'name' => $legacyFull,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => $validated['password'],
                'designation_id' => $validated['designation_id'] ?? null,
            ]);

            $user->profile()->create($this->extractProfilePayload($validated, $user->id));
            $user->loadMissing('profile');

            return $user;
        });

        ActivityLogger::log(
            $viewer,
            'user.created',
            'User account created',
            "{$createdUser->full_name} ({$createdUser->email})",
            '#7c3aed',
            $createdUser,
            ['role' => $createdUser->role instanceof UserRole ? $createdUser->role->value : (string) $createdUser->role]
        );

        if ($createdUser->isEmployeeRecord()) {
            return redirect()
                ->route('employees.overview', $createdUser)
                ->with('status', 'Employee created successfully.')
                ->with('show_employee_created_banner', true);
        }

        return redirect()
            ->route('admin.users.edit', $createdUser)
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
            'supervisorOptions' => $this->supervisorOptions($user->id),
            'designationOptions' => $this->designationOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatePayload($request, $user);
        $viewer = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $first = trim((string) $validated['first_name']);
            $middle = ($validated['middle_name'] ?? null) ? trim((string) $validated['middle_name']) : null;
            $last = trim((string) $validated['last_name']);
            $legacyFull = trim(implode(' ', array_values(array_filter([$first, $middle, $last], fn($p) => (string)$p !== ''))));

            $userPayload = [
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'name' => $legacyFull,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'designation_id' => $validated['designation_id'] ?? null,
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = $validated['password'];
            }

            $user->update($userPayload);
            $user->profile()->updateOrCreate([], $this->extractProfilePayload($validated, $user->id));
        });

        ActivityLogger::log(
            $viewer,
            'user.updated',
            'User account updated',
            "{$user->full_name} ({$user->email})",
            '#ec4899',
            $user,
            ['role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role]
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

        $meta = "{$user->full_name} ({$user->email})";
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
            ? ['nullable', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()]
            : ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()];
        $supervisorRules = ['nullable', 'integer', Rule::exists('users', 'id')];
        if ($userId !== null) {
            $supervisorRules[] = 'different:'.$userId;
        }

        $hasActiveDesignations = Designation::query()->where('is_active', true)->exists();

        // Backward compatibility for legacy 'name' field
        $input = $request->all();
        $hasStructured = isset($input['first_name']) || isset($input['last_name']);
        if (! $hasStructured && isset($input['name'])) {
            $name = trim((string) $input['name']);
            if ($name !== '') {
                $parts = preg_split('/\s+/', $name) ?: [];
                if (count($parts) === 1) {
                    $input['first_name'] = $parts[0];
                } elseif (count($parts) > 1) {
                    $input['first_name'] = array_shift($parts) ?? '';
                    $input['last_name'] = trim(implode(' ', $parts));
                }
                $request->replace($input);
            }
        }

        return $request->validate([
            'first_name' => ['required', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'middle_name' => ['nullable', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'last_name' => ['required', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(UserRole::values())],
            'password' => $passwordRules,

            // Designation: required for all except super_admin; only allow active designations
            'designation_id' => array_filter([
                $hasActiveDesignations ? ('required_unless:role,' . UserRole::SUPER_ADMIN->value) : 'nullable',
                'nullable',
                'integer',
                Rule::exists('designations', 'id')->where(function ($query): void {
                    $query->where('is_active', true);
                }),
            ]),

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
            'supervisor_user_id' => $supervisorRules,
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
        ], [
            'first_name.not_regex' => 'First name cannot be numbers only.',
            'middle_name.not_regex' => 'Middle name cannot be numbers only.',
            'last_name.not_regex' => 'Last name cannot be numbers only.',
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function extractProfilePayload(array $validated, ?int $managedUserId = null): array
    {
        $supervisorUserId = (int) ($validated['supervisor_user_id'] ?? 0);
        if ($managedUserId !== null && $supervisorUserId === $managedUserId) {
            $supervisorUserId = 0;
        }

        $supervisorName = null;
        if ($supervisorUserId > 0) {
            $supervisor = User::query()
                ->whereKey($supervisorUserId)
                ->first(['first_name', 'middle_name', 'last_name', 'name']);
            $supervisorName = $supervisor?->full_name ?? ($supervisor->name ?? null);
        }

        $managerName = $supervisorName ?: (($validated['manager_name'] ?? null) ?: null);

        return [
            'is_employee' => User::shouldTreatRoleAsEmployee((string) ($validated['role'] ?? '')),
            'employee_code' => User::shouldTreatRoleAsEmployee((string) ($validated['role'] ?? ''))
                ? User::makeEmployeeCode($managedUserId ?? 0)
                : null,
            'phone' => ($validated['phone'] ?? null) ?: null,
            'alternate_phone' => ($validated['alternate_phone'] ?? null) ?: null,
            'department' => ($validated['department'] ?? null) ?: null,
            'branch' => ($validated['branch'] ?? null) ?: null,
            'job_title' => ($validated['job_title'] ?? null) ?: null,
            'employment_type' => $validated['employment_type'],
            'status' => $validated['status'],
            'joined_on' => ($validated['joined_on'] ?? null) ?: null,
            'date_of_birth' => ($validated['date_of_birth'] ?? null) ?: null,
            'gender' => ($validated['gender'] ?? null) ?: null,
            'marital_status' => ($validated['marital_status'] ?? null) ?: null,
            'nationality' => ($validated['nationality'] ?? null) ?: null,
            'national_id' => ($validated['national_id'] ?? null) ?: null,
            'work_location' => ($validated['work_location'] ?? null) ?: null,
            'manager_name' => $managerName,
            'supervisor_user_id' => $supervisorUserId > 0 ? $supervisorUserId : null,
            'linkedin_url' => ($validated['linkedin_url'] ?? null) ?: null,
            'address' => ($validated['address'] ?? null) ?: null,
            'emergency_contact_name' => ($validated['emergency_contact_name'] ?? null) ?: null,
            'emergency_contact_phone' => ($validated['emergency_contact_phone'] ?? null) ?: null,
        ];
    }
}
