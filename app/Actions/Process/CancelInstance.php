<?php

namespace App\Actions\Process;

use App\Events\ProcessInstanceUpdated;
use App\Models\ProcessInstance;
use App\Models\User;
use App\States\ProcessInstance\Cancelled;
use App\States\StepExecution\Skipped;
use Illuminate\Support\Facades\DB;

class CancelInstance
{
    public function handle(ProcessInstance $instance, User $user, string $reason): void
    {
        DB::transaction(function () use ($instance, $user, $reason) {
            $instance->completed_at = now(); // Mark as ended

            // ADR-016: State transition
            $instance->status->transitionTo(Cancelled::class);

            // ADR-005: Audit Log
            activity()
                ->performedOn($instance)
                ->causedBy($user)
                ->withProperties(['reason' => $reason])
                ->log('cancelled');

            // Cancel all active steps
            $activeSteps = $instance->stepExecutions()
                ->whereIn('status', ['pending', 'in_progress', 'blocked', 'escalated'])
                ->get();

            foreach ($activeSteps as $step) {
                $step->completed_at = now();
                $step->completed_by = $user->id;
                $step->completion_notes = 'CANCELLED WITH INSTANCE: '.$reason;
                $step->status->transitionTo(Skipped::class);

                activity()
                    ->performedOn($step)
                    ->causedBy($user)
                    ->withProperties(['reason' => $reason])
                    ->log('cancelled');
            }

            event(new ProcessInstanceUpdated($instance));
        });
    }
}
