<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = 'Password@123';

        $admin = User::updateOrCreate([
            'email' => 'admin@hrm.test',
        ], [
            'name' => 'System Admin',
            'role' => UserRole::ADMIN->value,
            'password' => $defaultPassword,
            'email_verified_at' => now(),
        ]);

        $hr = User::updateOrCreate([
            'email' => 'hr@hrm.test',
        ], [
            'name' => 'HR Manager',
            'role' => UserRole::HR->value,
            'password' => $defaultPassword,
            'email_verified_at' => now(),
        ]);

        $employee = User::updateOrCreate([
            'email' => 'employee@hrm.test',
        ], [
            'name' => 'Test Employee',
            'role' => UserRole::EMPLOYEE->value,
            'password' => $defaultPassword,
            'email_verified_at' => now(),
        ]);

        UserProfile::updateOrCreate([
            'user_id' => $admin->id,
        ], [
            'department' => 'Administration',
            'job_title' => 'System Administrator',
            'status' => 'active',
            'employment_type' => 'full_time',
            'joined_on' => now()->subYears(3)->toDateString(),
        ]);

        UserProfile::updateOrCreate([
            'user_id' => $hr->id,
        ], [
            'department' => 'Human Resources',
            'job_title' => 'HR Manager',
            'status' => 'active',
            'employment_type' => 'full_time',
            'joined_on' => now()->subYears(2)->toDateString(),
        ]);

        UserProfile::updateOrCreate([
            'user_id' => $employee->id,
        ], [
            'department' => 'Operations',
            'job_title' => 'Staff',
            'status' => 'active',
            'employment_type' => 'full_time',
            'joined_on' => now()->subYear()->toDateString(),
        ]);
    }
}
