<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeesModulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_employees_module_page(): void
    {
        $this->get(route('modules.employees.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_sees_employee_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'department' => 'Engineering',
            'job_title' => 'Software Engineer',
            'status' => 'active',
            'employment_type' => 'full_time',
        ]);

        $this->actingAs($admin)
            ->get(route('modules.employees.index'))
            ->assertOk()
            ->assertSee('Employee Directory')
            ->assertSee('Engineering')
            ->assertDontSee('My Employee Details');
    }

    public function test_employee_sees_personal_employee_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'name' => 'Employee One',
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'department' => 'People Ops',
            'job_title' => 'Coordinator',
            'status' => 'active',
            'employment_type' => 'full_time',
        ]);

        $this->actingAs($employee)
            ->get(route('modules.employees.index'))
            ->assertOk()
            ->assertSee('My Employee Details')
            ->assertSee('People Ops')
            ->assertDontSee('Employee Directory');
    }

    public function test_hr_sees_employee_management_page(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($hr)
            ->get(route('modules.employees.index'))
            ->assertOk()
            ->assertSee('Employee Directory')
            ->assertDontSee('My Employee Details');
    }
}
