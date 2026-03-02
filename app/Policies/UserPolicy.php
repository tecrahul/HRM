<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'users.view',
            'users.view.all',
        ]);
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
        return $user->hasPermission('users.create');
    }

    /**
     * Determine if the user can update users.
     */
    public function update(User $user, User $targetUser): bool
    {
        return $user->hasPermission('users.edit');
    }

    /**
     * Determine if the user can delete users.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Cannot delete yourself
        if ((int) $user->id === (int) $targetUser->id) {
            return false;
        }

        return $user->hasPermission('users.delete');
    }

    /**
     * Determine if the user can manage user roles.
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasPermission('users.manage_roles');
    }
}
