<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    /**
     * Display the roles and permissions overview.
     */
    public function index(): View
    {
        $roles = Role::withCount('permissions', 'users')->get();
        $permissions = Permission::withCount('roles')->get();

        // Group permissions by module
        $permissionsByModule = $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });

        return view('settings.roles-permissions.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionsByModule' => $permissionsByModule,
            'totalRoles' => $roles->count(),
            'totalPermissions' => $permissions->count(),
        ]);
    }

    /**
     * Show the form for editing a role's permissions.
     */
    public function editRole(Role $role): View
    {
        $allPermissions = Permission::all();

        // Group permissions by module for better organization
        $permissionsByModule = $allPermissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return ucfirst($parts[0] ?? 'other');
        });

        // Get current role permissions
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('settings.roles-permissions.edit-role', [
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * Update the role's permissions.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            // Sync permissions
            $permissions = Permission::whereIn('id', $validated['permissions'] ?? [])->get();
            $role->syncPermissions($permissions);

            DB::commit();

            // Clear permission cache
            Artisan::call('rbac:clear-cache');

            return redirect()
                ->route('settings.roles-permissions.index')
                ->with('success', "Role '{$role->name}' permissions updated successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update role permissions: ' . $e->getMessage());
        }
    }

    /**
     * Show details of a specific role.
     */
    public function showRole(Role $role): View
    {
        $role->loadCount('permissions', 'users');
        $permissions = $role->permissions;

        // Group permissions by module
        $permissionsByModule = $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return ucfirst($parts[0] ?? 'other');
        });

        return view('settings.roles-permissions.show-role', [
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
        ]);
    }

    /**
     * Show details of a specific permission.
     */
    public function showPermission(Permission $permission): View
    {
        $permission->loadCount('roles', 'users');
        $roles = $permission->roles;

        return view('settings.roles-permissions.show-permission', [
            'permission' => $permission,
            'roles' => $roles,
        ]);
    }

    /**
     * Clear all RBAC caches.
     */
    public function clearCache(Request $request)
    {
        try {
            Artisan::call('rbac:clear-cache');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'RBAC caches cleared successfully.',
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'RBAC caches cleared successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear cache: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    /**
     * Sync users from legacy role column.
     */
    public function syncUsers(Request $request)
    {
        try {
            $force = $request->boolean('force', false);

            Artisan::call('rbac:sync-users', $force ? ['--force' => true] : []);

            $output = Artisan::output();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Users synced successfully.',
                    'output' => $output,
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Users synced successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync users: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to sync users: ' . $e->getMessage());
        }
    }

    /**
     * Check RBAC system health.
     */
    public function health(Request $request)
    {
        try {
            Artisan::call('rbac:health');
            $output = Artisan::output();

            // Parse output for status
            $isHealthy = str_contains($output, '✓ RBAC system is healthy');

            // Get statistics
            $stats = [
                'total_roles' => Role::count(),
                'total_permissions' => Permission::count(),
                'total_users_with_roles' => DB::table('model_has_roles')
                    ->distinct('model_id')
                    ->count('model_id'),
                'total_role_permission_assignments' => DB::table('role_has_permissions')->count(),
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'healthy' => $isHealthy,
                    'output' => $output,
                    'stats' => $stats,
                ]);
            }

            return view('settings.roles-permissions.health', [
                'output' => $output,
                'isHealthy' => $isHealthy,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check health: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to check health: ' . $e->getMessage());
        }
    }
}
