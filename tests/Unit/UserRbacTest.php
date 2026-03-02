<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed RBAC roles and permissions
        $this->seed(RbacSeeder::class);
    }

    public function test_user_can_check_role_with_rbac(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_user_can_check_any_role_with_rbac(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        $this->assertTrue($user->hasAnyRole(['hr', 'admin']));
        $this->assertFalse($user->hasAnyRole(['super_admin', 'finance']));
    }

    public function test_user_can_check_permission_with_rbac(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        $this->assertTrue($user->hasPermission('attendance.view.all'));
        $this->assertTrue($user->hasPermission('leave.approve'));
        $this->assertFalse($user->hasPermission('settings.smtp'));
    }

    public function test_user_can_check_any_permission_with_rbac(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $user->assignRole('employee');

        $this->assertTrue($user->hasAnyPermission(['attendance.view', 'leave.apply']));
        $this->assertFalse($user->hasAnyPermission(['users.delete', 'payroll.generate']));
    }

    public function test_user_falls_back_to_legacy_role_when_no_rbac_role(): void
    {
        // Create user with legacy role but no RBAC role
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        // Don't assign RBAC role

        // Should still work via legacy role column
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_falls_back_to_legacy_permission_when_no_rbac_role(): void
    {
        // Create user with legacy role but no RBAC role
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        // Don't assign RBAC role

        // Should work via legacy config/permissions.php
        $this->assertTrue($user->hasPermission('attendance.view.all'));
    }

    public function test_rbac_active_check_is_cached(): void
    {
        // Clear cache
        User::clearRbacCache();

        $user = User::factory()->create();

        // First check
        $reflection = new \ReflectionClass($user);
        $method = $reflection->getMethod('rbacActive');
        $method->setAccessible(true);

        $result1 = $method->invoke(null);
        $result2 = $method->invoke(null);

        // Both should be true and identical (cached)
        $this->assertTrue($result1);
        $this->assertSame($result1, $result2);
    }

    public function test_permission_check_is_cached(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        // Clear permission cache
        User::clearRbacCache();

        // First check - hits database
        $result1 = $user->hasPermission('users.view.all');

        // Second check - should use cache
        $result2 = $user->hasPermission('users.view.all');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $user->assignRole('super_admin');

        $allPermissions = Permission::all();

        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $user->hasPermission($permission->name),
                "Super admin should have permission: {$permission->name}"
            );
        }
    }

    public function test_employee_has_limited_permissions(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $user->assignRole('employee');

        // Employee should have these
        $this->assertTrue($user->hasPermission('attendance.view'));
        $this->assertTrue($user->hasPermission('leave.apply'));

        // Employee should NOT have these
        $this->assertFalse($user->hasPermission('users.delete'));
        $this->assertFalse($user->hasPermission('payroll.generate'));
        $this->assertFalse($user->hasPermission('settings.smtp'));
    }
}
