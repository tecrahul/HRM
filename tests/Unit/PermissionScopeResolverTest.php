<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PermissionScopeResolver;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionScopeResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed RBAC roles and permissions
        $this->seed(RbacSeeder::class);
    }

    public function test_resolve_returns_all_scope_for_all_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $this->assertEquals('all', $scope);
    }

    public function test_resolve_returns_department_scope_for_department_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::FINANCE->value]);
        $user->assignRole('finance');

        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $this->assertEquals('department', $scope);
    }

    public function test_resolve_returns_self_scope_for_self_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $user->assignRole('employee');

        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $this->assertEquals('self', $scope);
    }

    public function test_resolve_returns_null_for_no_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $user->assignRole('employee');

        // Employee doesn't have users.delete permission
        $scope = PermissionScopeResolver::resolve($user, 'users.delete');

        $this->assertNull($scope);
    }

    public function test_resolve_caches_results(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        // Clear cache
        PermissionScopeResolver::clearCache();

        // First call
        $scope1 = PermissionScopeResolver::resolve($user, 'leave.view');

        // Second call - should use cache
        $scope2 = PermissionScopeResolver::resolve($user, 'leave.view');

        $this->assertEquals('all', $scope1);
        $this->assertEquals($scope1, $scope2);
    }

    public function test_resolve_prevents_reentry(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        // This should not cause infinite loop due to re-entry guard
        $reflection = new \ReflectionClass(PermissionScopeResolver::class);
        $property = $reflection->getProperty('isResolving');
        $property->setAccessible(true);
        $property->setValue(null, true);

        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $this->assertNull($scope, 'Re-entry guard should return null');

        // Reset
        $property->setValue(null, false);
    }

    public function test_resolve_works_with_legacy_permissions(): void
    {
        // Create user with legacy role only (no RBAC role assigned)
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        // Don't assign RBAC role

        // Clear RBAC cache to force legacy mode
        PermissionScopeResolver::clearCache();

        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $this->assertEquals('all', $scope, 'Should fall back to legacy config');
    }

    public function test_clear_cache_resets_all_static_caches(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        // Populate caches
        PermissionScopeResolver::resolve($user, 'attendance.view');

        // Clear caches
        PermissionScopeResolver::clearCache();

        $reflection = new \ReflectionClass(PermissionScopeResolver::class);

        $resolveCache = $reflection->getProperty('resolveCache');
        $resolveCache->setAccessible(true);

        $permissionIdCache = $reflection->getProperty('permissionIdCache');
        $permissionIdCache->setAccessible(true);

        $this->assertEmpty($resolveCache->getValue(), 'Resolve cache should be empty');
        $this->assertEmpty($permissionIdCache->getValue(), 'Permission ID cache should be empty');
    }
}
