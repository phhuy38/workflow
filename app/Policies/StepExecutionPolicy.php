<?php

namespace App\Policies;

use App\Models\User;

class StepExecutionPolicy
{
    public function view(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'process_designer', 'executor']);
    }

    public function complete(User $user): bool
    {
        return $user->hasPermissionTo('complete_assigned_steps');
    }

    public function escalate(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}
