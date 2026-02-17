<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchesModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_branches_module(): void
    {
        $this->get(route('modules.branches.index'))
            ->assertRedirect(route('login'));
    }

    public function test_hr_cannot_access_branches_module(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($hr)
            ->get(route('modules.branches.index'))
            ->assertForbidden();
    }

    public function test_employee_cannot_access_branches_module(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.branches.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_and_edit_branch(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($admin)
            ->post(route('modules.branches.store'), [
                'name' => 'New York HQ',
                'code' => 'NYHQ',
                'location' => 'New York, USA',
                'description' => 'Primary office',
                'is_active' => '1',
            ])
            ->assertRedirect(route('modules.branches.index'));

        $branch = Branch::query()->where('code', 'NYHQ')->first();
        $this->assertNotNull($branch);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'branch' => 'New York HQ',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('modules.branches.update', $branch), [
                'name' => 'NY Main Office',
                'code' => 'NY-MAIN',
                'location' => 'Manhattan, New York',
                'description' => 'Updated office details',
                'is_active' => '1',
            ])
            ->assertRedirect(route('modules.branches.index'));

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'NY Main Office',
            'code' => 'NY-MAIN',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $employee->id,
            'branch' => 'NY Main Office',
        ]);
    }

    public function test_branch_sidebar_link_is_visible_for_admin_only(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.branches.index'))
            ->assertOk()
            ->assertSee(route('modules.branches.index'));

        $this->actingAs($hr)
            ->get(route('modules.departments.index'))
            ->assertOk()
            ->assertDontSee(route('modules.branches.index'));
    }

    public function test_branch_cannot_be_deleted_when_assigned_to_employee(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $branch = Branch::query()->create([
            'name' => 'Austin',
            'code' => 'AUS',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'branch' => 'Austin',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete(route('modules.branches.destroy', $branch))
            ->assertRedirect(route('modules.branches.index'))
            ->assertSessionHas('error', 'Cannot delete branch. It is assigned to 1 employee(s).');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
        ]);
    }

    public function test_admin_can_delete_unassigned_branch(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $branch = Branch::query()->create([
            'name' => 'Berlin',
            'code' => 'BER',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('modules.branches.destroy', $branch))
            ->assertRedirect(route('modules.branches.index'))
            ->assertSessionHas('status', 'Branch deleted successfully.');

        $this->assertDatabaseMissing('branches', [
            'id' => $branch->id,
        ]);
    }
}
