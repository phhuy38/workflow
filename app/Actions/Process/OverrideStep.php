<?php

namespace App\Actions\Process;

use App\Models\StepExecution;
use App\Models\User;
use App\States\StepExecution\Skipped;
use Illuminate\Support\Facades\DB;

class OverrideStep
{
    public function __construct(private AdvanceProcessInstance $advanceProcess) {}

    public function handle(StepExecution $step, User $user, string $reason): void
    {
        DB::transaction(function () use ($step, $user, $reason) {
            $step->completed_at = now();
            $step->completed_by = $user->id;
            $step->completion_notes = 'OVERRIDDEN: '.$reason;

            // ADR-016: State transition
            $step->status->transitionTo(Skipped::class);

            // ADR-005: Audit Log
            activity()
                ->performedOn($step)
                ->causedBy($user)
                ->withProperties(['reason' => $reason])
                ->log('overridden');

            // Move to the next step
            $this->advanceProcess->handle($step->instance, $step, $user);
        });
    }
}
