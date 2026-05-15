<?php

namespace App\Actions\Process;

use App\Models\ProcessInstance;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Completed as ProcessCompleted;
use App\States\StepExecution\Completed as StepCompleted;
use Illuminate\Support\Facades\DB;

class CompleteStep
{
    public function __construct(private AdvanceProcessInstance $advanceProcess)
    {
    }

    public function handle(StepExecution $step, User $user, array $data = []): void
    {
        DB::transaction(function () use ($step, $user, $data) {
            $step->completed_at = now();
            $step->completed_by = $user->id;
            $step->completion_notes = $data['completion_notes'] ?? null;
            // ADR-016: State transition
            $step->status->transitionTo(StepCompleted::class);

            // ADR-005: Audit Log
            activity()
                ->performedOn($step)
                ->causedBy($user)
                ->log('completed');

            $this->advanceProcess->handle($step->instance, $step, $user);
        });
    }
}

