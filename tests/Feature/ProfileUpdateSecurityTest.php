<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_profile_update_cannot_override_assignment_fields(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'name' => 'Employee One',
        ]);

        UserProfile::query()->create([
            'user_id' => $employee->id,
            'phone' => '+1 555 0100',
            'manager_name' => 'Original Manager',
            'work_location' => 'HQ',
        ]);

        $response = $this->actingAs($employee)->put(route('profile.update'), [
            'name' => 'Updated Employee',
            'phone' => '+1 555 0200',
            'manager_name' => 'Tampered Manager',
            'work_location' => 'Unauthorized Branch',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Employee',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $employee->id,
            'phone' => '+1 555 0200',
            'manager_name' => 'Original Manager',
            'work_location' => 'HQ',
        ]);
    }
}
