<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentsModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_departments_module(): void
    {
        $this->get(route('modules.departments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_departments_module(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.departments.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_and_create_department(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.departments.index'))
            ->assertOk()
            ->assertSee('Department Management');

        $this->actingAs($admin)
            ->post(route('modules.departments.store'), [
                'name' => 'Engineering',
                'code' => 'ENG',
                'description' => 'Core engineering department',
                'is_active' => '1',
            ])
            ->assertRedirect(route('modules.departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);
    }

    public function test_hr_can_access_and_create_department(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($hr)
            ->get(route('modules.departments.index'))
            ->assertOk();

        $this->actingAs($hr)
            ->post(route('modules.departments.store'), [
                'name' => 'People Operations',
                'code' => 'HROPS',
                'is_active' => '1',
            ])
            ->assertRedirect(route('modules.departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'People Operations',
            'code' => 'HROPS',
        ]);
    }

    public function test_department_link_not_visible_for_employee_sidebar(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.employees.index'))
            ->assertOk()
            ->assertDontSee(route('modules.departments.index'));
    }

    public function test_department_link_visible_for_hr_sidebar(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        Department::query()->create([
            'name' => 'Finance',
            'code' => 'FIN',
            'is_active' => true,
        ]);

        $this->actingAs($hr)
            ->get(route('modules.departments.index'))
            ->assertOk()
            ->assertSee(route('modules.departments.index'));
    }
}
