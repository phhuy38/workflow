<?php

namespace App\Actions\User;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class AssignDesignerRole
{
    public function handle(User $actor, User $target): void
    {
        $target->assignRole('process_designer');

        // Clear permission cache after role assignment (ADR-010)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        activity()->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['role' => 'process_designer', 'action' => 'assign'])
            ->log('role_assigned');
    }
}
