<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_users') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_users') || $user->id === $model->id;
    }

    public function deactivate(User $user, User $model): bool
    {
        // Admin cannot deactivate themselves
        return $user->hasPermissionTo('manage_users') && $user->id !== $model->id;
    }
}
