<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserWizardController extends Controller
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
        return ['full_time', 'part_time', 'contract', 'intern'];
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'middle_name' => ['nullable', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'last_name' => ['required', 'string', 'max:120', 'not_regex:/^\d+$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(UserRole::values())],
            'status' => ['required', Rule::in($this->profileStatuses())],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'first_name.not_regex' => 'First name cannot be numbers only.',
            'middle_name.not_regex' => 'Middle name cannot be numbers only.',
            'last_name.not_regex' => 'Last name cannot be numbers only.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
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
            ]);

            // Create minimal profile with status; remaining details will be filled in subsequent steps
            $user->profile()->create([
                'is_employee' => User::shouldTreatRoleAsEmployee($validated['role']),
                'employee_code' => User::shouldTreatRoleAsEmployee($validated['role']) ? User::makeEmployeeCode((int) $user->id) : null,
                'status' => $validated['status'],
            ]);

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->full_name,
                'email' => (string) $user->email,
            ],
        ], 201);
    }

    public function updateEmployment(Request $request, User $user): JsonResponse
    {
        $supervisorRules = ['nullable', 'integer', Rule::exists('users', 'id')];
        $supervisorRules[] = 'different:'.$user->id;

        $hasActiveDesignations = Designation::query()->where('is_active', true)->exists();

        $validated = $request->validate([
            'designation_id' => array_filter([
                $hasActiveDesignations ? 'nullable' : 'nullable',
                'nullable',
                'integer',
                Rule::exists('designations', 'id')->where(function ($query): void {
                    $query->where('is_active', true);
                }),
            ]),
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:120'],
            'supervisor_user_id' => $supervisorRules,
            'manager_name' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', Rule::in($this->employmentTypes())],
            'joined_on' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'designation_id' => $validated['designation_id'] ?? null,
            ]);

            $payload = [
                'job_title' => ($validated['job_title'] ?? null) ?: null,
                'department' => ($validated['department'] ?? null) ?: null,
                'branch' => ($validated['branch'] ?? null) ?: null,
                'employment_type' => $validated['employment_type'],
                'joined_on' => ($validated['joined_on'] ?? null) ?: null,
            ];

            $supervisorUserId = (int) ($validated['supervisor_user_id'] ?? 0);
            if ($supervisorUserId > 0 && $supervisorUserId !== (int) $user->id) {
                $payload['supervisor_user_id'] = $supervisorUserId;
                // Auto-fill manager name if not provided
                $supervisor = User::query()->whereKey($supervisorUserId)->first(['first_name', 'middle_name', 'last_name', 'name']);
                $payload['manager_name'] = $validated['manager_name'] ?? ($supervisor?->full_name ?? ($supervisor->name ?? null));
            } else {
                $payload['supervisor_user_id'] = null;
                $payload['manager_name'] = ($validated['manager_name'] ?? null) ?: null;
            }

            $user->profile()->updateOrCreate([], $payload);
        });

        return response()->json([
            'success' => true,
            'message' => 'Employment details saved.',
        ]);
    }

    public function updatePersonal(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'alternate_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            // Optional fields for future schema support; accepted but ignored if not present in DB
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $payload = [
                'date_of_birth' => ($validated['date_of_birth'] ?? null) ?: null,
                'gender' => ($validated['gender'] ?? null) ?: null,
                'phone' => ($validated['phone'] ?? null) ?: null,
                'alternate_phone' => ($validated['alternate_phone'] ?? null) ?: null,
                'address' => ($validated['address'] ?? null) ?: null,
                'emergency_contact_name' => ($validated['emergency_contact_name'] ?? null) ?: null,
                'emergency_contact_phone' => ($validated['emergency_contact_phone'] ?? null) ?: null,
            ];

            $user->profile()->updateOrCreate([], $payload);
        });

        return response()->json([
            'success' => true,
            'message' => 'Personal details saved.',
            'data' => [
                'redirect_url' => route('employees.overview', $user),
            ],
        ]);
    }
}
