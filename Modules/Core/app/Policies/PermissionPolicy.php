<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Permission;

final class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view any permissions.
     */
    public function viewAny(Admin $user): bool
    {
        // Only admins or admins with specific permission can view all permissions
        return $user->hasRole('admin') || $user->checkPermissionTo('view any permissions');
    }

    /**
     * Determine whether the admin can view the permission.
     */
    public function view(Admin $user, Permission $permission): bool
    {
        // Super-admins can view any permission
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can view permissions
        return $user->checkPermissionTo('view permissions');
    }

    /**
     * Determine whether the admin can create permissions.
     */
    public function create(Admin $user): bool
    {
        // Only admins can create new permissions
        return $user->hasRole('admin') || $user->checkPermissionTo('create permissions');
    }

    /**
     * Determine whether the admin can update the permission.
     */
    public function update(Admin $user, Permission $permission): bool
    {
        // Only admins can update permissions
        if ($user->hasRole('admin')) {
            // Cannot modify core system permissions
            return ! $this->isCorePermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the admin can delete the permission.
     */
    public function delete(Admin $user, Permission $permission): bool
    {
        // Only admins can delete permissions
        if ($user->hasRole('admin')) {
            // Cannot delete core system permissions
            return ! $this->isCorePermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the admin can restore the permission.
     */
    public function restore(Admin $user, Permission $permission): bool
    {
        // Only admins can restore permissions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the admin can permanently delete the permission.
     */
    public function forceDelete(Admin $user, Permission $permission): bool
    {
        // Only admins can force delete permissions
        if ($user->hasRole('admin')) {
            // Cannot force delete core system permissions
            return ! $this->isCorePermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the admin can assign permissions to roles.
     */
    public function assignToRole(Admin $user, Permission $permission): bool
    {
        // Super-admins can assign any permission to roles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can assign non-core permissions
        if ($user->checkPermissionTo('assign permissions')) {
            return ! $this->isCorePermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the admin can revoke permissions from roles.
     */
    public function revokeFromRole(Admin $user, Permission $permission): bool
    {
        // Super-admins can revoke any permission from roles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can revoke non-core permissions
        if ($user->checkPermissionTo('revoke permissions')) {
            return ! $this->isCorePermission($permission);
        }

        return false;
    }

    /**
     * Determine whether the admin can bulk delete permissions.
     */
    public function deleteAny(Admin $user): bool
    {
        // Only admins can bulk delete permissions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the admin can export permissions.
     */
    public function export(Admin $user): bool
    {
        // Super-admins and admins with permission can export permissions
        return $user->hasRole('admin') || $user->checkPermissionTo('export permissions');
    }

    /**
     * Determine whether the admin can import permissions.
     */
    public function import(Admin $user): bool
    {
        // Only admins can import permissions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the admin can sync permissions.
     */
    public function sync(Admin $user): bool
    {
        // Only admins can sync permissions
        return $user->hasRole('admin');
    }

    /**
     * Check if a permission is a core system permission that shouldn't be modified.
     */
    private function isCorePermission(Permission $permission): bool
    {
        $corePermissions = [
            'view any admins',
            'view admins',
            'create admins',
            'update admins',
            'delete admins',
            'manage system settings',
            'view activity logs',
            'view any roles',
            'view roles',
            'create roles',
            'update roles',
            'delete roles',
            'assign roles',
            'revoke roles',
            'manage role permissions',
            'view any permissions',
            'view permissions',
            'create permissions',
            'update permissions',
            'delete permissions',
            'assign permissions',
            'revoke permissions',
        ];

        return in_array($permission->name, $corePermissions);
    }
}
