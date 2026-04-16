<?php

namespace App\Actions\User;

use App\Events\UserDeactivated;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeactivateUser
{
    public function handle(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new AuthorizationException('Admin cannot deactivate their own account.');
        }

        $target->update(['is_active' => false]);

        // Invalidate all active sessions for the deactivated user (P1 security)
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
        }

        activity()->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['action' => 'deactivate'])
            ->log('user_deactivated');

        UserDeactivated::dispatch($target);
    }
}
