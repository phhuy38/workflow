<?php

namespace App\Actions\Process;

use App\Models\StepExecution;
use App\Models\User;
use App\States\StepExecution\InProgress;
use Illuminate\Support\Facades\DB;

class AcknowledgeStep
{
    public function handle(StepExecution $step, User $user): void
    {
        DB::transaction(function () use ($step, $user) {
            $step->started_at = now();
            // ADR-016: State transition (automatically saves dirty attributes)
            $step->status->transitionTo(InProgress::class);

            // ADR-005: Audit Log
            activity()
                ->performedOn($step)
                ->causedBy($user)
                ->log('acknowledged');

            event(new \App\Events\StepExecutionUpdated($step));
            event(new \App\Events\ProcessInstanceUpdated($step->instance));
        });
    }
}
