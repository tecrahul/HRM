<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserProfile;
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

    public function test_admin_can_edit_department_and_sync_employee_profile_department_name(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $department = Department::query()->create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'department' => 'Engineering',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('modules.departments.update', $department), [
                'name' => 'Product Engineering',
                'code' => 'P-ENG',
                'description' => 'Updated unit',
                'is_active' => '1',
            ])
            ->assertRedirect(route('modules.departments.index'))
            ->assertSessionHas('status', 'Department updated successfully.');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Product Engineering',
            'code' => 'P-ENG',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $employee->id,
            'department' => 'Product Engineering',
        ]);
    }

    public function test_department_cannot_be_deleted_when_assigned_to_employee(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $department = Department::query()->create([
            'name' => 'Finance',
            'code' => 'FIN',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'department' => 'Finance',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete(route('modules.departments.destroy', $department))
            ->assertRedirect(route('modules.departments.index'))
            ->assertSessionHas('error', 'Cannot delete department. It is assigned to 1 employee(s).');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
        ]);
    }

    public function test_admin_can_delete_unassigned_department(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $department = Department::query()->create([
            'name' => 'Audit',
            'code' => 'AUD',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('modules.departments.destroy', $department))
            ->assertRedirect(route('modules.departments.index'))
            ->assertSessionHas('status', 'Department deleted successfully.');

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    }
}
