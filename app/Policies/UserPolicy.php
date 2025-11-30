<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['users.view-any', 'super-admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->hasPermission('super-admin')) {
            return true;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->organization_id === $model->organization_id &&
               $user->hasPermission('users.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['users.create', 'super-admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->hasPermission('super-admin')) {
            return true;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->organization_id === $model->organization_id &&
               $user->hasPermission('users.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->hasPermission('super-admin')) {
            return true;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->organization_id === $model->organization_id &&
               $user->hasPermission('users.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if ($user->hasPermission('super-admin')) {
            return true;
        }

        return $user->organization_id === $model->organization_id &&
               $user->hasPermission('users.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->hasPermission('super-admin');
    }
}
