<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder is idempotent and can be run multiple times safely.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Starting RBAC setup...');

        // Step 1: Create all permissions
        $this->createPermissions();

        // Step 2: Create all roles
        $this->createRoles();

        // Step 3: Assign permissions to roles
        $this->assignPermissionsToRoles();

        // Step 4: Sync existing users from legacy role column
        $this->syncExistingUsers();

        $this->command->info('RBAC setup completed successfully!');
    }

    /**
     * Create all permissions for the HRM system.
     */
    protected function createPermissions(): void
    {
        $this->command->info('Creating permissions...');

        $permissions = $this->getAllPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Created ' . count($permissions) . ' permissions.');
    }

    /**
     * Get all permissions for the HRM system.
     * Returns array of permission names.
     */
    protected function getAllPermissions(): array
    {
        return [
            // User Management
            'users.view',
            'users.view.all',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage_roles',

            // Employee Directory
            'employees.view',
            'employees.view.all',
            'employees.create',
            'employees.edit',
            'employees.delete',
            'employees.view.profile',
            'employees.edit.profile',

            // Department Management
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // Designation Management
            'designations.view',
            'designations.create',
            'designations.edit',
            'designations.delete',

            // Branch Management
            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

            // Attendance Module
            'attendance.view',              // View own attendance
            'attendance.view.self',         // View own attendance (alias)
            'attendance.view.department',   // View department attendance
            'attendance.view.all',          // View all attendance
            'attendance.create',            // Create/mark attendance
            'attendance.edit',              // Edit attendance
            'attendance.delete',            // Delete attendance
            'attendance.approve',           // Approve attendance
            'attendance.reject',            // Reject attendance
            'attendance.lock.month',        // Lock attendance month
            'attendance.unlock.month',      // Unlock attendance month
            'attendance.export',            // Export attendance data

            // Leave Management
            'leave.view',                   // View own leaves
            'leave.view.all',               // View all leaves
            'leave.view.department',        // View department leaves
            'leave.apply',                  // Apply for leave
            'leave.create',                 // Create leave (alias)
            'leave.edit',                   // Edit own leave
            'leave.cancel',                 // Cancel own leave
            'leave.approve',                // Approve leave requests
            'leave.reject',                 // Reject leave requests
            'leave.delete',                 // Delete leave requests

            // Payroll Module
            'payroll.view',                 // View own payroll
            'payroll.view.all',             // View all payroll
            'payroll.generate',             // Generate payroll
            'payroll.approve',              // Approve payroll
            'payroll.pay',                  // Mark as paid
            'payroll.edit',                 // Edit payroll
            'payroll.delete',               // Delete payroll
            'payroll.manage_structure',     // Manage salary structures
            'payroll.export',               // Export payroll data

            // Reports & Analytics
            'reports.view',                 // View reports
            'reports.export',               // Export reports
            'reports.view.analytics',       // View analytics dashboard
            'reports.activity',             // View activity reports
            'reports.attendance',           // View attendance reports
            'reports.leave',                // View leave reports
            'reports.payroll',              // View payroll reports

            // Communication
            'messages.view',                // View own messages
            'messages.send',                // Send messages
            'messages.delete',              // Delete own messages
            'messages.broadcast',           // Send broadcast messages
            'conversations.view',           // View conversations
            'conversations.create',         // Create conversations

            // Holiday Management
            'holiday.view',                 // View holidays
            'holiday.create',               // Create holidays
            'holiday.edit',                 // Edit holidays
            'holiday.delete',               // Delete holidays

            // Settings & Configuration
            'settings.view',                // View settings
            'settings.edit',                // Edit settings
            'settings.smtp',                // Manage SMTP settings
            'settings.company',             // Manage company settings
            'settings.auth_features',       // Manage auth features (2FA, signup, etc.)

            // Dashboard Access
            'dashboard.admin',              // Access admin dashboard
            'dashboard.hr',                 // Access HR dashboard
            'dashboard.finance',            // Access finance dashboard
            'dashboard.employee',           // Access employee dashboard
        ];
    }

    /**
     * Create all roles matching UserRole enum values.
     */
    protected function createRoles(): void
    {
        $this->command->info('Creating roles...');

        $roles = [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
            UserRole::EMPLOYEE->value,
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Created ' . count($roles) . ' roles.');
    }

    /**
     * Assign permissions to roles based on existing config/permissions.php logic.
     */
    protected function assignPermissionsToRoles(): void
    {
        $this->command->info('Assigning permissions to roles...');

        // Get permission mapping from config (for backward compatibility)
        $permissionMap = config('permissions.map', []);

        // Define comprehensive role-permission mapping
        $rolePermissions = $this->getRolePermissionMapping();

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName);

            // Sync permissions (remove old, add new)
            $role->syncPermissions($permissions);

            $this->command->info("  {$roleName}: " . count($permissions) . ' permissions');
        }

        $this->command->info('Permissions assigned successfully!');
    }

    /**
     * Get comprehensive role-permission mapping.
     */
    protected function getRolePermissionMapping(): array
    {
        return [
            UserRole::SUPER_ADMIN->value => [
                // Super Admin gets ALL permissions
                'users.view', 'users.view.all', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles',
                'employees.view', 'employees.view.all', 'employees.create', 'employees.edit', 'employees.delete', 'employees.view.profile', 'employees.edit.profile',
                'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                'designations.view', 'designations.create', 'designations.edit', 'designations.delete',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'attendance.view', 'attendance.view.self', 'attendance.view.department', 'attendance.view.all', 'attendance.create', 'attendance.edit', 'attendance.delete', 'attendance.approve', 'attendance.reject', 'attendance.lock.month', 'attendance.unlock.month', 'attendance.export',
                'leave.view', 'leave.view.all', 'leave.view.department', 'leave.apply', 'leave.create', 'leave.edit', 'leave.cancel', 'leave.approve', 'leave.reject', 'leave.delete',
                'payroll.view', 'payroll.view.all', 'payroll.generate', 'payroll.approve', 'payroll.pay', 'payroll.edit', 'payroll.delete', 'payroll.manage_structure', 'payroll.export',
                'reports.view', 'reports.export', 'reports.view.analytics', 'reports.activity', 'reports.attendance', 'reports.leave', 'reports.payroll',
                'messages.view', 'messages.send', 'messages.delete', 'messages.broadcast',
                'conversations.view', 'conversations.create',
                'holiday.view', 'holiday.create', 'holiday.edit', 'holiday.delete',
                'settings.view', 'settings.edit', 'settings.smtp', 'settings.company', 'settings.auth_features',
                'dashboard.admin', 'dashboard.hr', 'dashboard.finance', 'dashboard.employee',
            ],

            UserRole::ADMIN->value => [
                // Admin gets most permissions except some sensitive ones
                'users.view', 'users.view.all', 'users.create', 'users.edit', 'users.delete',
                'employees.view', 'employees.view.all', 'employees.create', 'employees.edit', 'employees.delete', 'employees.view.profile', 'employees.edit.profile',
                'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                'designations.view', 'designations.create', 'designations.edit', 'designations.delete',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'attendance.view', 'attendance.view.self', 'attendance.view.department', 'attendance.view.all', 'attendance.create', 'attendance.edit', 'attendance.delete', 'attendance.approve', 'attendance.reject', 'attendance.lock.month', 'attendance.unlock.month', 'attendance.export',
                'leave.view', 'leave.view.all', 'leave.view.department', 'leave.apply', 'leave.create', 'leave.edit', 'leave.cancel', 'leave.approve', 'leave.reject', 'leave.delete',
                'payroll.view', 'payroll.view.all', 'payroll.generate', 'payroll.approve', 'payroll.pay', 'payroll.edit', 'payroll.delete', 'payroll.manage_structure', 'payroll.export',
                'reports.view', 'reports.export', 'reports.view.analytics', 'reports.activity', 'reports.attendance', 'reports.leave', 'reports.payroll',
                'messages.view', 'messages.send', 'messages.delete', 'messages.broadcast',
                'conversations.view', 'conversations.create',
                'holiday.view', 'holiday.create', 'holiday.edit', 'holiday.delete',
                'settings.view', 'settings.edit', 'settings.company',
                'dashboard.admin', 'dashboard.hr', 'dashboard.finance', 'dashboard.employee',
            ],

            UserRole::HR->value => [
                // HR focuses on employee, attendance, leave management
                'users.view', 'users.view.all',
                'employees.view', 'employees.view.all', 'employees.create', 'employees.edit', 'employees.view.profile', 'employees.edit.profile',
                'departments.view', 'departments.create', 'departments.edit',
                'designations.view', 'designations.create', 'designations.edit',
                'branches.view',
                'attendance.view', 'attendance.view.self', 'attendance.view.department', 'attendance.view.all', 'attendance.create', 'attendance.edit', 'attendance.delete', 'attendance.approve', 'attendance.reject', 'attendance.lock.month', 'attendance.export',
                'leave.view', 'leave.view.all', 'leave.view.department', 'leave.apply', 'leave.create', 'leave.edit', 'leave.cancel', 'leave.approve', 'leave.reject', 'leave.delete',
                'payroll.view', 'payroll.view.all', 'payroll.generate', 'payroll.edit', 'payroll.manage_structure', 'payroll.export',
                'reports.view', 'reports.export', 'reports.view.analytics', 'reports.activity', 'reports.attendance', 'reports.leave', 'reports.payroll',
                'messages.view', 'messages.send', 'messages.delete', 'messages.broadcast',
                'conversations.view', 'conversations.create',
                'holiday.view', 'holiday.create', 'holiday.edit', 'holiday.delete',
                'settings.view',
                'dashboard.hr', 'dashboard.employee',
            ],

            UserRole::FINANCE->value => [
                // Finance focuses on payroll and reports
                'users.view',
                'employees.view', 'employees.view.all', 'employees.view.profile',
                'departments.view',
                'designations.view',
                'branches.view',
                'attendance.view', 'attendance.view.self', 'attendance.view.department', 'attendance.export',
                'leave.view',
                'payroll.view', 'payroll.view.all', 'payroll.generate', 'payroll.approve', 'payroll.pay', 'payroll.edit', 'payroll.manage_structure', 'payroll.export',
                'reports.view', 'reports.export', 'reports.view.analytics', 'reports.payroll', 'reports.attendance',
                'messages.view', 'messages.send', 'messages.delete',
                'conversations.view', 'conversations.create',
                'holiday.view',
                'settings.view',
                'dashboard.finance', 'dashboard.employee',
            ],

            UserRole::EMPLOYEE->value => [
                // Employee gets basic self-service permissions
                'users.view',
                'employees.view', 'employees.view.profile',
                'departments.view',
                'designations.view',
                'branches.view',
                'attendance.view', 'attendance.view.self', 'attendance.create',
                'leave.view', 'leave.apply', 'leave.create', 'leave.edit', 'leave.cancel',
                'payroll.view',
                'reports.view',
                'messages.view', 'messages.send', 'messages.delete',
                'conversations.view', 'conversations.create',
                'holiday.view',
                'settings.view',
                'dashboard.employee',
            ],
        ];
    }

    /**
     * Sync existing users from legacy role column to Spatie roles.
     */
    protected function syncExistingUsers(): void
    {
        $this->command->info('Syncing existing users to RBAC roles...');

        $users = User::all();
        $syncedCount = 0;

        foreach ($users as $user) {
            // Skip if user has no role
            if (! $user->role) {
                continue;
            }

            // Get role value (handle both enum and string)
            $roleValue = $user->role instanceof UserRole
                ? $user->role->value
                : (string) $user->role;

            try {
                // Check if role exists in Spatie
                $role = Role::findByName($roleValue);

                // Assign role to user if not already assigned
                if (! $user->hasRole($roleValue)) {
                    $user->assignRole($role);
                    $syncedCount++;
                    $this->command->info("  Synced user #{$user->id} ({$user->email}) to role: {$roleValue}");
                }
            } catch (\Throwable $e) {
                $this->command->warn("  Failed to sync user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->command->info("Synced {$syncedCount} users to RBAC roles.");
    }
}
