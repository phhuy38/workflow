<?php

namespace App\Policies;

use App\Models\ProcessInstance;
use App\Models\User;

class ProcessInstancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_all_instances');
    }

    /**
     * Ownership-aware check (ADR-004):
     * - admin/manager/process_designer: always allowed
     * - executor: only if assigned to a step in this instance
     * - beneficiary: only if instance was created for them
     */
    public function view(User $user, ProcessInstance $instance): bool
    {
        return match (true) {
            $user->hasRole(['admin', 'manager', 'process_designer']) => true,
            $user->hasRole('executor') && $this->isExecutorAssignedToInstance($user, $instance) => true,
            $user->hasRole('beneficiary') && $this->isBeneficiaryForInstance($user, $instance) => true,
            default => false,
        };
    }

    /**
     * Check if executor is assigned to any step in this instance.
     */
    private function isExecutorAssignedToInstance(User $user, ProcessInstance $instance): bool
    {
        return $instance->stepExecutions()
            ->where('assigned_to', $user->id)
            ->exists();
    }

    /**
     * Check if beneficiary is the recipient of this instance.
     */
    private function isBeneficiaryForInstance(User $user, ProcessInstance $instance): bool
    {
        // For now, created_for might be stored in context_data or a dedicated column.
        // PRD says Manager provides list of beneficiaries.
        // ADR-017 mentions created_for.
        return $instance->created_for === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('launch_instances');
    }

    public function update(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function delete(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole('admin');
    }

    public function cancel(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $instance->launched_by);
    }

    public function override(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('manager') && $user->id === $instance->launched_by);
    }

    public function ping(User $user, ProcessInstance $instance): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function viewFullLog(User $user, ProcessInstance $instance): bool
    {
        // UX-DR9: Only managers and admins can see the full granular tracking
        return $user->hasRole(['admin', 'manager']);
    }
}
