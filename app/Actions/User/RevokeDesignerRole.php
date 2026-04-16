<?php

namespace App\Actions\User;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class RevokeDesignerRole
{
    public function handle(User $actor, User $target): void
    {
        $target->removeRole('process_designer');

        // Clear permission cache after role revocation (ADR-010)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        activity()->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['role' => 'process_designer', 'action' => 'revoke'])
            ->log('role_revoked');
    }
}
