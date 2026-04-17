<?php

namespace App\Listeners;

use App\Events\UserDeactivated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReassignOpenStepsOnUserDeactivated
{
    public function handle(UserDeactivated $event): void
    {
        // Graceful: step_executions table does not exist until Story 3.x
        if (! Schema::hasTable('step_executions')) {
            return;
        }

        DB::table('step_executions')
            ->where('assigned_to', $event->user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->update([
                'assigned_to' => null,
                'status' => 'pending',
            ]);
    }
}
