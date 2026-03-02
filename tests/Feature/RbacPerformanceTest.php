<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\NotificationCenter;
use App\Support\PermissionScopeResolver;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RbacPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed RBAC roles and permissions
        $this->seed(RbacSeeder::class);
    }

    /** @test */
    public function permission_check_completes_without_infinite_loop(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        // Set a timeout - if infinite loop occurs, test will fail
        set_time_limit(5);

        $startTime = microtime(true);

        // This should complete quickly without infinite loop
        $result = $user->hasPermission('users.view.all');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertTrue($result);
        $this->assertLessThan(100, $duration, 'Permission check took too long (>100ms)');
    }

    /** @test */
    public function multiple_permission_checks_are_fast_due_to_caching(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        // First check - may hit database
        $start1 = microtime(true);
        $user->hasPermission('attendance.view.all');
        $duration1 = (microtime(true) - $start1) * 1000;

        // Subsequent checks - should use cache
        $start2 = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $user->hasPermission('attendance.view.all');
        }
        $duration2 = (microtime(true) - $start2) * 1000;

        // 100 cached checks should be faster than 1 database check
        $this->assertLessThan($duration1 * 50, $duration2, 'Caching is not working efficiently');
    }

    /** @test */
    public function scope_resolver_does_not_cause_infinite_loop(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        set_time_limit(5);

        $startTime = microtime(true);

        // This should complete quickly without infinite loop
        $scope = PermissionScopeResolver::resolve($user, 'attendance.view');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertEquals('all', $scope);
        $this->assertLessThan(50, $duration, 'Scope resolution took too long (>50ms)');
    }

    /** @test */
    public function scope_resolver_uses_caching_effectively(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $user->assignRole('admin');

        PermissionScopeResolver::clearCache();

        // First call
        $start1 = microtime(true);
        PermissionScopeResolver::resolve($user, 'leave.view');
        $duration1 = (microtime(true) - $start1) * 1000;

        // Second call - should use cache
        $start2 = microtime(true);
        PermissionScopeResolver::resolve($user, 'leave.view');
        $duration2 = (microtime(true) - $start2) * 1000;

        // Cached call should be much faster
        $this->assertLessThan($duration1 / 2, $duration2, 'Cache is not speeding up scope resolution');
    }

    /** @test */
    public function notification_sync_does_not_cause_infinite_loop(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        NotificationCenter::clearCache();

        set_time_limit(5);

        $startTime = microtime(true);

        // This should complete quickly without infinite loop
        NotificationCenter::syncFor($admin);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(500, $duration, 'Notification sync took too long (>500ms)');
    }

    /** @test */
    public function notification_sync_does_not_run_twice_for_same_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        NotificationCenter::clearCache();

        // Enable query logging
        DB::enableQueryLog();

        // First sync
        NotificationCenter::syncFor($user);
        $queriesAfterFirstSync = count(DB::getQueryLog());

        // Second sync - should be skipped due to cache
        NotificationCenter::syncFor($user);
        $queriesAfterSecondSync = count(DB::getQueryLog());

        // No additional queries should be run
        $this->assertEquals(
            $queriesAfterFirstSync,
            $queriesAfterSecondSync,
            'Second sync should not execute any queries due to per-user cache'
        );
    }

    /** @test */
    public function page_load_with_permission_checks_is_fast(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        $startTime = microtime(true);

        // Simulate multiple permission checks on a page
        $permissions = [
            'users.view.all',
            'attendance.view.all',
            'leave.approve',
            'payroll.generate',
            'reports.view.analytics',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            $admin->hasPermission($permission);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // 6 permission checks should complete in less than 100ms
        $this->assertLessThan(100, $duration, 'Multiple permission checks took too long');
    }

    /** @test */
    public function database_queries_are_optimized(): void
    {
        $user = User::factory()->create(['role' => UserRole::HR->value]);
        $user->assignRole('hr');

        // Clear all caches
        User::clearRbacCache();
        PermissionScopeResolver::clearCache();

        DB::enableQueryLog();
        DB::flushQueryLog();

        // Perform various permission checks
        $user->hasPermission('attendance.view.all');
        $user->hasPermission('leave.approve');
        $user->hasAnyPermission(['payroll.view', 'reports.export']);
        PermissionScopeResolver::resolve($user, 'attendance.view');

        $queryCount = count(DB::getQueryLog());

        // Should be efficient with caching - expect less than 20 queries
        $this->assertLessThan(20, $queryCount, 'Too many database queries for permission checks');
    }

    /** @test */
    public function no_n_plus_one_queries_with_multiple_users(): void
    {
        // Create multiple users
        $users = User::factory()->count(10)->create();

        foreach ($users as $user) {
            $user->role = UserRole::EMPLOYEE->value;
            $user->save();
            $user->assignRole('employee');
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        // Check permissions for all users
        foreach ($users as $user) {
            $user->hasPermission('attendance.view');
        }

        $queryCount = count(DB::getQueryLog());

        // Should not scale linearly with number of users due to caching
        // Expect less than 30 queries for 10 users
        $this->assertLessThan(30, $queryCount, 'N+1 query problem detected');
    }
}
