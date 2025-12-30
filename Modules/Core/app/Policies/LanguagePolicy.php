<?php

declare(strict_types=1);

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Admin;
use Modules\Core\Models\Localization\Language;

final class LanguagePolicy
{
    use HandlesAuthorization;

    public function viewAny(Admin $user): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('view any languages');
    }

    public function view(Admin $user, Language $language): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('view languages');
    }

    public function create(Admin $user): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('create languages');
    }

    public function update(Admin $user, Language $language): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('update languages');
    }

    public function delete(Admin $user, Language $language): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('delete languages');
    }

    public function restore(Admin $user, Language $language): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('restore languages');
    }

    public function forceDelete(Admin $user, Language $language): bool
    {
        return $user->hasRole('admin') || $user->checkPermissionTo('force delete languages');
    }
}
