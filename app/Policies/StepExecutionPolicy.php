<?php

namespace App\Policies;

use App\Models\StepExecution;
use App\Models\User;

class StepExecutionPolicy
{
    /**
     * Determine if the user can acknowledge/complete the step.
     * Rule: Only the assigned user or an admin/manager (for future override) can act.
     * In this story, we focus on the Executor (assigned user).
     */
    public function update(User $user, StepExecution $step): bool
    {
        return $user->id === $step->assigned_to || $user->hasRole(['admin', 'manager']);
    }

    public function acknowledge(User $user, StepExecution $step): bool
    {
        if ($step->instance->status->getValue() !== 'running') {
            return false;
        }
        return ($user->id === $step->assigned_to || $user->hasRole(['admin', 'manager'])) && $step->status->getValue() === 'pending';
    }

    public function complete(User $user, StepExecution $step): bool
    {
        if ($step->instance->status->getValue() !== 'running') {
            return false;
        }
        return ($user->id === $step->assigned_to || $user->hasRole(['admin', 'manager'])) && $step->status->getValue() === 'in_progress';
    }

    public function addMessage(User $user, StepExecution $step): bool
    {
        if (in_array($step->status->getValue(), ['completed', 'cancelled', 'skipped'])) {
            return false;
        }

        return match (true) {
            $user->hasRole(['admin', 'manager']) => true,
            $user->id == $step->assigned_to => true,
            $user->hasRole('beneficiary') && $step->instance->created_for == $user->id => true,
            default => false,
        };
    }
}
