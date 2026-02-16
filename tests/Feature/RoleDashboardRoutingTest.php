<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_route_redirects_hr_to_hr_dashboard(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($hr)
            ->get(route('dashboard'))
            ->assertRedirect(route('hr.dashboard'));
    }

    public function test_dashboard_route_redirects_employee_to_employee_dashboard(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_root_route_redirects_authenticated_user_to_role_dashboard(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get('/')
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_admin_dashboard_allows_hr_role(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($hr)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }
}
