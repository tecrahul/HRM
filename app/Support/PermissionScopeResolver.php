<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permission Scope Resolver
 *
 * Resolves the scope of permissions for a user: 'all', 'department', 'branch', 'self', or null.
 * Uses static in-memory caching to prevent performance issues.
 *
 * CRITICAL: This class uses static caching and re-entry guards to avoid:
 * - Infinite loops
 * - N+1 database queries
 * - Circular permission checks
 */
class PermissionScopeResolver
{
    /**
     * Static cache for scope resolution results.
     * Key: "user_id:permission_name", Value: scope string or null
     */
    private static array $resolveCache = [];

    /**
     * Static cache for permission ID lookups.
     * Key: "permission_name", Value: permission ID
     */
    private static array $permissionIdCache = [];

    /**
     * Static cache for schema checks.
     */
    private static ?bool $hasScopeTable = null;

    /**
     * Static cache for RBAC active check.
     */
    private static ?bool $rbacActive = null;

    /**
     * Re-entry guard to prevent infinite loops.
     */
    private static bool $isResolving = false;

    /**
     * Resolve the scope for a user's permission.
     *
     * Returns one of: 'all', 'department', 'branch', 'self', or null
     *
     * @param User $user
     * @param string $permission Base permission name (e.g., 'attendance.view')
     * @return string|null
     */
    public static function resolve(User $user, string $permission): ?string
    {
        // Re-entry guard: prevent infinite loops
        if (self::$isResolving) {
            return null;
        }

        // Check static cache first
        $cacheKey = $user->id . ':' . $permission;
        if (isset(self::$resolveCache[$cacheKey])) {
            return self::$resolveCache[$cacheKey];
        }

        // Set re-entry guard
        self::$isResolving = true;

        try {
            $scope = self::performResolve($user, $permission);

            // Cache the result
            self::$resolveCache[$cacheKey] = $scope;

            return $scope;
        } catch (\Throwable $e) {
            // Log error but don't throw - return null to fail safely
            \Log::debug('PermissionScopeResolver error: ' . $e->getMessage());

            return null;
        } finally {
            // Always clear re-entry guard
            self::$isResolving = false;
        }
    }

    /**
     * Perform the actual scope resolution.
     * This is separated from resolve() to allow proper re-entry guard management.
     */
    protected static function performResolve(User $user, string $permission): ?string
    {
        // Check if RBAC is active
        if (! self::isRbacActive()) {
            // Fall back to legacy scope logic based on role
            return self::resolveLegacyScope($user, $permission);
        }

        // Check scopes in order: all -> department -> branch -> self
        // Using direct database queries to avoid triggering hasPermission() checks

        // Check for .all scope
        if (self::userHasPermissionDirect($user, "{$permission}.all")) {
            return 'all';
        }

        // Check for .department scope
        if (self::userHasPermissionDirect($user, "{$permission}.department")) {
            return 'department';
        }

        // Check for .branch scope (if you implement branch-level permissions)
        if (self::userHasPermissionDirect($user, "{$permission}.branch")) {
            return 'branch';
        }

        // Check for .self or base permission (both mean 'self')
        if (
            self::userHasPermissionDirect($user, "{$permission}.self")
            || self::userHasPermissionDirect($user, $permission)
        ) {
            return 'self';
        }

        // No permission found
        return null;
    }

    /**
     * Check if user has permission using direct database query.
     * This avoids triggering hasPermission() which could cause infinite loops.
     *
     * @param User $user
     * @param string $permissionName
     * @return bool
     */
    protected static function userHasPermissionDirect(User $user, string $permissionName): bool
    {
        try {
            // Get permission ID from cache or database
            $permissionId = self::getPermissionId($permissionName);

            if (! $permissionId) {
                return false;
            }

            // Check if user has this permission directly
            $hasDirectPermission = DB::table('model_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->exists();

            if ($hasDirectPermission) {
                return true;
            }

            // Check if user has this permission through a role
            $hasRolePermission = DB::table('model_has_roles')
                ->join('role_has_permissions', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
                ->where('role_has_permissions.permission_id', $permissionId)
                ->where('model_has_roles.model_type', get_class($user))
                ->where('model_has_roles.model_id', $user->id)
                ->exists();

            return $hasRolePermission;
        } catch (\Throwable $e) {
            \Log::debug('userHasPermissionDirect error: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get permission ID by name, using cache.
     *
     * @param string $permissionName
     * @return int|null
     */
    protected static function getPermissionId(string $permissionName): ?int
    {
        // Check cache first
        if (isset(self::$permissionIdCache[$permissionName])) {
            return self::$permissionIdCache[$permissionName];
        }

        try {
            $permission = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission) {
                self::$permissionIdCache[$permissionName] = (int) $permission->id;

                return (int) $permission->id;
            }

            // Cache null results too to avoid repeated lookups
            self::$permissionIdCache[$permissionName] = null;

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if RBAC system is active (tables exist).
     */
    protected static function isRbacActive(): bool
    {
        if (self::$rbacActive !== null) {
            return self::$rbacActive;
        }

        try {
            self::$rbacActive = Schema::hasTable('permissions')
                && Schema::hasTable('roles')
                && Schema::hasTable('model_has_permissions');
        } catch (\Throwable $e) {
            self::$rbacActive = false;
        }

        return self::$rbacActive;
    }

    /**
     * Resolve scope using legacy config/permissions.php logic.
     * Used as fallback when RBAC is not active.
     *
     * @param User $user
     * @param string $permission
     * @return string|null
     */
    protected static function resolveLegacyScope(User $user, string $permission): ?string
    {
        $permissionMap = config('permissions.map', []);

        // Check for .all scope
        if (isset($permissionMap["{$permission}.all"])) {
            $allowedRoles = $permissionMap["{$permission}.all"];
            if (self::userHasAnyRoleLegacy($user, $allowedRoles)) {
                return 'all';
            }
        }

        // Check for .department scope
        if (isset($permissionMap["{$permission}.department"])) {
            $allowedRoles = $permissionMap["{$permission}.department"];
            if (self::userHasAnyRoleLegacy($user, $allowedRoles)) {
                return 'department';
            }
        }

        // Check for .branch scope
        if (isset($permissionMap["{$permission}.branch"])) {
            $allowedRoles = $permissionMap["{$permission}.branch"];
            if (self::userHasAnyRoleLegacy($user, $allowedRoles)) {
                return 'branch';
            }
        }

        // Check for .self or base permission
        if (
            isset($permissionMap["{$permission}.self"])
            || isset($permissionMap[$permission])
        ) {
            $allowedRoles = $permissionMap["{$permission}.self"] ?? $permissionMap[$permission] ?? [];
            if (self::userHasAnyRoleLegacy($user, $allowedRoles)) {
                return 'self';
            }
        }

        return null;
    }

    /**
     * Check if user has any of the given roles (legacy mode).
     *
     * @param User $user
     * @param array $roles
     * @return bool
     */
    protected static function userHasAnyRoleLegacy(User $user, array $roles): bool
    {
        if (! is_array($roles) || empty($roles)) {
            return false;
        }

        $userRole = $user->role instanceof \App\Enums\UserRole
            ? $user->role->value
            : (string) $user->role;

        return in_array($userRole, $roles, true);
    }

    /**
     * Clear all static caches.
     * Useful for testing and after permission changes.
     */
    public static function clearCache(): void
    {
        self::$resolveCache = [];
        self::$permissionIdCache = [];
        self::$hasScopeTable = null;
        self::$rbacActive = null;
    }
}
