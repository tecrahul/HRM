<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Get the role hierarchy level (higher number = more privileged).
     */
    private function getRoleLevel(UserRole|string|null $role): int
    {
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        return match ($role) {
            'super_admin', UserRole::SUPER_ADMIN->value => 5,
            'admin', UserRole::ADMIN->value => 4,
            'hr', UserRole::HR->value => 3,
            'finance', UserRole::FINANCE->value => 3,
            'employee', UserRole::EMPLOYEE->value => 1,
            default => 0,
        };
    }

    /**
     * Check if the user has sufficient privileges to manage the target user.
     */
    private function canManageRole(User $user, User $targetUser): bool
    {
        $userLevel = $this->getRoleLevel($user->role);
        $targetLevel = $this->getRoleLevel($targetUser->role);

        // Super admins can manage anyone
        if ($userLevel === 5) {
            return true;
        }

        // Regular users cannot manage users with equal or higher privilege
        return $userLevel > $targetLevel;
    }

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view')
            || $user->hasPermissionTo('users.view.all');
    }

    /**
     * Determine if the user can view a specific user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Use PermissionScopeResolver to determine scope
        $scope = PermissionScopeResolver::resolve($user, 'users.view');

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'self') {
            return (int) $user->id === (int) $targetUser->id;
        }

        // No permission
        return false;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine if the user can update users.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Must have the permission
        if (! $user->hasPermissionTo('users.edit')) {
            return false;
        }

        // Cannot edit yourself through user management (use profile page instead)
        if ((int) $user->id === (int) $targetUser->id) {
            return false;
        }

        // Check role hierarchy - cannot edit users with equal or higher privilege
        return $this->canManageRole($user, $targetUser);
    }

    /**
     * Determine if the user can delete users.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Must have the permission
        if (! $user->hasPermissionTo('users.delete')) {
            return false;
        }

        // Cannot delete yourself
        if ((int) $user->id === (int) $targetUser->id) {
            return false;
        }

        // Check role hierarchy - cannot delete users with equal or higher privilege
        return $this->canManageRole($user, $targetUser);
    }

    /**
     * Determine if the user can manage user roles.
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasPermissionTo('users.manage_roles');
    }
}
