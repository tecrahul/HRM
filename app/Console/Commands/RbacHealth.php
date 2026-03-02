<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:health
                            {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check RBAC system health and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking RBAC system health...');
        $this->newLine();

        $issues = 0;

        // Check 1: Tables exist
        $issues += $this->checkTables();

        // Check 2: Roles exist
        $issues += $this->checkRoles();

        // Check 3: Permissions exist
        $issues += $this->checkPermissions();

        // Check 4: Role-Permission assignments
        $issues += $this->checkRolePermissions();

        // Check 5: User-Role assignments
        $issues += $this->checkUserRoles();

        // Check 6: Cache configuration
        $issues += $this->checkCacheConfig();

        // Check 7: Spatie config
        $issues += $this->checkSpatieConfig();

        // Summary
        $this->newLine();
        if ($issues === 0) {
            $this->info('✓ RBAC system is healthy! No issues found.');

            return self::SUCCESS;
        } else {
            $this->warn("⚠ Found {$issues} issue(s) in RBAC system. Please review and fix.");

            return self::FAILURE;
        }
    }

    protected function checkTables(): int
    {
        $this->info('1. Checking RBAC tables...');

        $requiredTables = [
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];

        $issues = 0;

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->info("  ✓ {$table} table exists ({$count} records)");
            } else {
                $this->error("  ✗ {$table} table is missing!");
                $issues++;
            }
        }

        return $issues;
    }

    protected function checkRoles(): int
    {
        $this->info('2. Checking roles...');

        $expectedRoles = ['super_admin', 'admin', 'hr', 'finance', 'employee'];
        $issues = 0;

        $roles = Role::all();

        if ($roles->isEmpty()) {
            $this->error('  ✗ No roles found! Run: php artisan db:seed --class=RbacSeeder');
            $issues++;

            return $issues;
        }

        $this->info("  ✓ Found {$roles->count()} roles");

        foreach ($expectedRoles as $roleName) {
            if ($roles->where('name', $roleName)->isNotEmpty()) {
                $this->info("    ✓ {$roleName}");
            } else {
                $this->error("    ✗ Missing role: {$roleName}");
                $issues++;
            }
        }

        return $issues;
    }

    protected function checkPermissions(): int
    {
        $this->info('3. Checking permissions...');

        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            $this->error('  ✗ No permissions found! Run: php artisan db:seed --class=RbacSeeder');

            return 1;
        }

        $this->info("  ✓ Found {$permissions->count()} permissions");

        if ($this->option('detailed')) {
            $grouped = $permissions->groupBy(function ($permission) {
                return explode('.', $permission->name)[0] ?? 'other';
            });

            foreach ($grouped as $module => $perms) {
                $this->info("    {$module}: {$perms->count()} permissions");
            }
        }

        return 0;
    }

    protected function checkRolePermissions(): int
    {
        $this->info('4. Checking role-permission assignments...');

        $roles = Role::all();
        $issues = 0;

        foreach ($roles as $role) {
            $permissionCount = $role->permissions()->count();

            if ($permissionCount === 0) {
                $this->error("  ✗ Role '{$role->name}' has no permissions assigned!");
                $issues++;
            } else {
                $this->info("  ✓ {$role->name}: {$permissionCount} permissions");
            }
        }

        return $issues;
    }

    protected function checkUserRoles(): int
    {
        $this->info('5. Checking user-role assignments...');

        $totalUsers = User::count();
        $usersWithRoles = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->distinct('model_id')
            ->count('model_id');
        $usersWithoutRoles = $totalUsers - $usersWithRoles;

        $this->info("  Total users: {$totalUsers}");
        $this->info("  Users with RBAC roles: {$usersWithRoles}");

        if ($usersWithoutRoles > 0) {
            $this->warn("  ⚠ Users without RBAC roles: {$usersWithoutRoles}");
            $this->info("    Run: php artisan rbac:sync-users");

            return 1;
        } else {
            $this->info('  ✓ All users have RBAC roles assigned');
        }

        return 0;
    }

    protected function checkCacheConfig(): int
    {
        $this->info('6. Checking cache configuration...');

        $cacheDriver = config('cache.default');
        $permissionCacheStore = config('permission.cache.store', 'default');

        $this->info("  Cache driver: {$cacheDriver}");
        $this->info("  Permission cache store: {$permissionCacheStore}");

        if ($cacheDriver === 'database' || $permissionCacheStore === 'database') {
            $this->error('  ✗ CRITICAL: Database cache driver is NOT recommended for RBAC!');
            $this->info('    Update CACHE_STORE in .env to: file, redis, or memcached');

            return 1;
        } else {
            $this->info('  ✓ Cache configuration is good');
        }

        return 0;
    }

    protected function checkSpatieConfig(): int
    {
        $this->info('7. Checking Spatie configuration...');

        $issues = 0;

        $registerPermissionCheck = config('permission.register_permission_check_method', true);

        if ($registerPermissionCheck === true) {
            $this->error('  ✗ CRITICAL: register_permission_check_method is enabled!');
            $this->info('    Set to false in config/permission.php to prevent Gate conflicts');
            $issues++;
        } else {
            $this->info('  ✓ register_permission_check_method is disabled (correct)');
        }

        $eventsEnabled = config('permission.events_enabled', false);
        $this->info("  Events enabled: " . ($eventsEnabled ? 'yes' : 'no'));

        $cacheExpiration = config('permission.cache.expiration_time');
        $this->info("  Cache expiration: {$cacheExpiration->h} hours");

        return $issues;
    }
}

