<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can list all users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update another user's details.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change the model's role.
     */
    public function changeRole(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return $user->is($model)
            ? Response::deny(__('You cannot change your own role.'))
            : Response::allow();
    }

    /**
     * Determine whether the user can deactivate the model's account.
     */
    public function deactivate(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return $user->is($model)
            ? Response::deny(__('You cannot deactivate your own account from the admin panel.'))
            : Response::allow();
    }

    /**
     * Determine whether the user can reactivate the model's account.
     */
    public function reactivate(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
