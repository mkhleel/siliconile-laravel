<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view any roles.
     */
    public function viewAny(Admin $user): bool
    {
        // Only admins or admins with specific permission can view all roles
        return $user->hasRole('admin') || $user->checkPermissionTo('view any roles');
    }

    /**
     * Determine whether the admin can view the role.
     */
    public function view(Admin $user, Role $role): bool
    {
        // Super-admins can view any role
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can view roles
        if ($user->checkPermissionTo('view roles')) {
            // But cannot view admin role unless they are admin
            return $role->name !== 'admin';
        }

        return false;
    }

    /**
     * Determine whether the admin can create roles.
     */
    public function create(Admin $user): bool
    {
        // Only admins or admins with specific permission can create roles
        return $user->hasRole('admin') || $user->checkPermissionTo('create roles');
    }

    /**
     * Determine whether the admin can update the role.
     */
    public function update(Admin $user, Role $role): bool
    {
        // Super-admins can update any role except admin role
        if ($user->hasRole('admin')) {
            // Cannot modify the admin role
            return $role->name !== 'admin';
        }

        // Regular admins with permission can update roles
        if ($user->checkPermissionTo('update roles')) {
            // But cannot update admin or admin roles
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can delete the role.
     */
    public function delete(Admin $user, Role $role): bool
    {
        // Super-admins can delete roles except core system roles
        if ($user->hasRole('admin')) {
            // Cannot delete core system roles
            return ! in_array($role->name, ['admin', 'admin']);
        }

        // Regular admins with permission can delete non-admin roles
        if ($user->checkPermissionTo('delete roles')) {
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can restore the role.
     */
    public function restore(Admin $user, Role $role): bool
    {
        // Only admins or admins with specific permission can restore roles
        return $user->hasRole('admin') || $user->checkPermissionTo('restore roles');
    }

    /**
     * Determine whether the admin can permanently delete the role.
     */
    public function forceDelete(Admin $user, Role $role): bool
    {
        // Only admins can force delete roles
        if ($user->hasRole('admin')) {
            // Cannot force delete core system roles
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can assign the role to users.
     */
    public function assign(Admin $user, Role $role): bool
    {
        // Super-admins can assign any role except admin
        if ($user->hasRole('admin')) {
            // Only admins can assign admin role (but we restrict this for security)
            return $role->name !== 'admin';
        }

        // Regular admins with permission can assign non-admin roles
        if ($user->checkPermissionTo('assign roles')) {
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can revoke the role from users.
     */
    public function revoke(Admin $user, Role $role): bool
    {
        // Super-admins can revoke any role except admin from themselves
        if ($user->hasRole('admin')) {
            return $role->name !== 'admin';
        }

        // Regular admins with permission can revoke non-admin roles
        if ($user->checkPermissionTo('revoke roles')) {
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can manage permissions for the role.
     */
    public function managePermissions(Admin $user, Role $role): bool
    {
        // Only admins can manage permissions
        if ($user->hasRole('admin')) {
            // Cannot modify admin role permissions
            return $role->name !== 'admin';
        }

        // Regular admins with specific permission can manage permissions for non-admin roles
        if ($user->checkPermissionTo('manage role permissions')) {
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the admin can bulk delete roles.
     */
    public function deleteAny(Admin $user): bool
    {
        // Only admins can bulk delete roles
        return $user->hasRole('admin') || $user->checkPermissionTo('bulk delete roles');
    }

    /**
     * Determine whether the admin can export roles.
     */
    public function export(Admin $user): bool
    {
        // Super-admins and admins with permission can export roles
        return $user->hasRole('admin') || $user->checkPermissionTo('export roles');
    }

    /**
     * Determine whether the admin can import roles.
     */
    public function import(Admin $user): bool
    {
        // Only admins can import roles
        return $user->hasRole('admin') || $user->checkPermissionTo('import roles');
    }

    /**
     * Determine whether the admin can duplicate a role.
     */
    public function duplicate(Admin $user, Role $role): bool
    {
        // Super-admins can duplicate any role except admin
        if ($user->hasRole('admin')) {
            return $role->name !== 'admin';
        }

        // Regular admins with permission can duplicate non-admin roles
        if ($user->checkPermissionTo('duplicate roles')) {
            return ! in_array($role->name, ['admin', 'admin']);
        }

        return false;
    }
}
