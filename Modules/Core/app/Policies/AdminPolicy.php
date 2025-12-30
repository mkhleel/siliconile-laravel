<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Admin;

final class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view any admins.
     */
    public function viewAny(Admin $user): bool
    {
        // Only admins or admins with specific permission can view all admins
        return $user->hasRole('admin') || $user->checkPermissionTo('view any admins');
    }

    /**
     * Determine whether the admin can view the model.
     */
    public function view(Admin $user, Admin $admin): bool
    {
        // Admins can always view their own profile
        if ($user->id === $admin->id) {
            return true;
        }

        // Super-admins can view any admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can view other admins
        if ($user->checkPermissionTo('view admins')) {
            // But cannot view admins unless they are admin themselves
            return ! $admin->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine whether the admin can create admins.
     */
    public function create(Admin $user): bool
    {
        // Only admins or admins with specific permission can create new admins
        return $user->hasRole('admin') || $user->checkPermissionTo('create admins');
    }

    /**
     * Determine whether the admin can update the model.
     */
    public function update(Admin $user, Admin $admin): bool
    {
        // Admins can always update their own profile (basic info only)
        if ($user->id === $admin->id) {
            return true;
        }

        // Super-admins can update any admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular admins with permission can update other admins
        if ($user->checkPermissionTo('update admins')) {
            // But cannot update admins unless they are admin themselves
            return ! $admin->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine whether the admin can delete the model.
     */
    public function delete(Admin $user, Admin $admin): bool
    {
        // Admins cannot delete themselves
        if ($user->id === $admin->id) {
            return false;
        }

        // Super-admins can delete other admins (but not other admins)
        if ($user->hasRole('admin')) {
            // Cannot delete the primary admin (ID = 1)
            if ($admin->id === 1) {
                return false;
            }

            return true;
        }

        // Regular admins with permission can delete other regular admins only
        if ($user->checkPermissionTo('delete admins')) {
            return ! $admin->hasRole('admin') && ! $admin->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine whether the admin can restore the model.
     */
    public function restore(Admin $user, Admin $admin): bool
    {
        // Only admins or admins with specific permission can restore admins
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->checkPermissionTo('restore admins')) {
            // But cannot restore admins unless they are admin themselves
            return ! $admin->hasRole('admin');
        }

        return false;
    }

    /**
     * Determine whether the admin can permanently delete the model.
     */
    public function forceDelete(Admin $user, Admin $admin): bool
    {
        // Only admins can force delete admins
        if ($user->hasRole('admin')) {
            // Cannot force delete the primary admin (ID = 1)
            if ($admin->id === 1) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the admin can assign roles to other admins.
     */
    public function assignRoles(Admin $user, Admin $admin): bool
    {
        // Admins cannot assign roles to themselves
        if ($user->id === $admin->id) {
            return false;
        }

        // Only admins can assign roles
        if ($user->hasRole('admin')) {
            // Cannot modify the primary admin (ID = 1)
            return $admin->id !== 1;
        }

        return false;
    }

    /**
     * Determine whether the admin can manage admin permissions.
     */
    public function managePermissions(Admin $user): bool
    {
        // Only admins can manage permissions
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the admin can bulk delete admins.
     */
    public function deleteAny(Admin $user): bool
    {
        // Only admins can bulk delete admins
        return $user->hasRole('admin') || $user->checkPermissionTo('bulk delete admins');
    }

    /**
     * Determine whether the admin can export admin data.
     */
    public function export(Admin $user): bool
    {
        // Only admins or admins with specific permission can export admin data
        return $user->hasRole('admin') || $user->checkPermissionTo('export admins');
    }

    /**
     * Determine whether the admin can import admin data.
     */
    public function import(Admin $user): bool
    {
        // Only admins can import admin data
        return $user->hasRole('admin') || $user->checkPermissionTo('import admins');
    }

    /**
     * Determine whether the admin can access system settings.
     */
    public function manageSystemSettings(Admin $user): bool
    {
        // Only admins can manage system settings
        return $user->hasRole('admin') || $user->checkPermissionTo('manage system settings');
    }

    /**
     * Determine whether the admin can view activity logs.
     */
    public function viewActivityLogs(Admin $user): bool
    {
        // Super-admins and admins with permission can view activity logs
        return $user->hasRole('admin') || $user->checkPermissionTo('view activity logs');
    }
}
