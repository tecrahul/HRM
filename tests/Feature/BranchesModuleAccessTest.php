<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
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
}
