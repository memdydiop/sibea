<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('manage_users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage_users')
            && ($model->password_changed_at === null || $model->suspended_at !== null);
    }

    public function suspend(User $user, User $model): bool
    {
        return $user->can('manage_users') && $user->isNot($model);
    }

    public function unsuspend(User $user, User $model): bool
    {
        return $user->can('manage_users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('manage_users')
            && $user->isNot($model)
            && $model->suspended_at !== null;
    }
}
