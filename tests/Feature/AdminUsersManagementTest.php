<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_users_section(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_admin_users_section(): void
    {
        $employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_employee_cannot_access_admin_users_create_form(): void
    {
        $employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('admin.users.create'));

        $response->assertForbidden();
    }

    public function test_hr_can_access_admin_users_section(): void
    {
        $hrUser = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $response = $this->actingAs($hrUser)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_hr_can_create_user_with_profile(): void
    {
        $hrUser = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $payload = [
            'name' => 'HR Created User',
            'email' => 'hr.created@example.test',
            'role' => UserRole::EMPLOYEE->value,
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'department' => 'Operations',
            'branch' => 'Mumbai',
            'employment_type' => 'full_time',
            'status' => 'active',
        ];

        $response = $this->actingAs($hrUser)->post(route('admin.users.store'), $payload);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'hr.created@example.test',
            'role' => UserRole::EMPLOYEE->value,
        ]);
    }

    public function test_admin_can_create_user_with_profile(): void
    {
        $adminUser = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $payload = [
            'name' => 'New Team User',
            'email' => 'new.user@example.test',
            'role' => UserRole::EMPLOYEE->value,
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'phone' => '+1 (444) 222-1111',
            'department' => 'Engineering',
            'branch' => 'New York HQ',
            'job_title' => 'Developer',
            'employment_type' => 'full_time',
            'status' => 'active',
            'joined_on' => '2026-01-10',
            'manager_name' => 'Jordan Smith',
            'address' => '1 Main St',
            'emergency_contact_name' => 'Alex Doe',
            'emergency_contact_phone' => '+1 (444) 999-1111',
        ];

        $response = $this->actingAs($adminUser)->post(route('admin.users.store'), $payload);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.user@example.test',
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $newUser = User::query()->where('email', 'new.user@example.test')->first();
        $this->assertNotNull($newUser);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $newUser->id,
            'department' => 'Engineering',
            'branch' => 'New York HQ',
            'manager_name' => 'Jordan Smith',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_user_profile(): void
    {
        $adminUser = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $managedUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $managedUser->id,
            'department' => 'Operations',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $response = $this->actingAs($adminUser)->put(route('admin.users.update', $managedUser), [
            'name' => 'Updated User',
            'email' => $managedUser->email,
            'role' => UserRole::HR->value,
            'password' => '',
            'password_confirmation' => '',
            'department' => 'Human Resources',
            'branch' => 'Austin',
            'employment_type' => 'part_time',
            'status' => 'inactive',
            'manager_name' => 'Casey Johnson',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Updated User',
            'role' => UserRole::HR->value,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $managedUser->id,
            'department' => 'Human Resources',
            'branch' => 'Austin',
            'employment_type' => 'part_time',
            'status' => 'inactive',
            'manager_name' => 'Casey Johnson',
        ]);
    }
}
