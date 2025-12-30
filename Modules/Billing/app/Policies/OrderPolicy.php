<?php

namespace Modules\Billing\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Billing\Models\Order;
use Modules\Core\Models\Admin;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(Admin $user): bool
    {
        // Allow viewing any order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('view any orders');
    }

    public function view(Admin $user, Order $order): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('view orders');
    }

    public function create(Admin $user): bool
    {
        // Allow creating an order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('create orders');
    }

    public function update(Admin $user, Order $order): bool
    {
        // Allow updating an order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('update orders');
    }

    public function delete(Admin $user, Order $order): bool
    {
        // Allow deleting an order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('delete orders');
    }

    public function restore(Admin $user, Order $order): bool
    {
        // Allow restoring an order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('restore orders');
    }

    public function forceDelete(Admin $user, Order $order): bool
    {
        // Allow force deleting an order if the user is an admin or has the appropriate role
        return $user->hasRole('admin') || $user->checkPermissionTo('force delete orders');
    }
}
