<?php

namespace App\Policies;

use App\Models\User;

class ProcessInstancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_all_instances');
    }

    /**
     * Ownership-aware check (ADR-004, P6 patch):
     * - admin/manager/process_designer: always allowed
     * - executor: only if assigned to a step in this instance
     * - beneficiary: only if instance was created for them
     *
     * ProcessInstance model created in Story 3.1; $instance will be typed as ProcessInstance $instance then.
     */
    public function view(User $user, mixed $instance = null): bool
    {
        return match (true) {
            // P4: Accept mixed type for now (ProcessInstance model created in Story 3.1)
            $user->hasRole(['admin', 'manager', 'process_designer']) => true,
            // P6: Executor can view if assigned to a step in this instance
            $user->hasRole('executor') && $this->isExecutorAssignedToInstance($user, $instance) => true,
            // P6: Beneficiary can view if instance was created for them
            $user->hasRole('beneficiary') && $this->isBeneficiaryForInstance($user, $instance) => true,
            default => false,
        };
    }

    /**
     * Check if executor is assigned to any step in this instance (P6).
     * Requires: ProcessInstance model with stepExecutions() relation.
     */
    private function isExecutorAssignedToInstance(User $user, mixed $instance): bool
    {
        if (! is_object($instance) || ! method_exists($instance, 'stepExecutions')) {
            return false;
        }

        return $instance->stepExecutions()
            ->where('assigned_to', $user->id)
            ->exists();
    }

    /**
     * Check if beneficiary is the recipient of this instance (P6).
     * Requires: ProcessInstance model with created_for attribute/relation.
     */
    private function isBeneficiaryForInstance(User $user, mixed $instance): bool
    {
        if (! is_object($instance)) {
            return false;
        }

        return $instance->created_for === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('launch_instances');
    }

    public function cancel(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function override(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function ping(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}
