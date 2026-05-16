<?php

namespace App\Actions\Process;

use App\Events\ProcessInstanceUpdated;
use App\Events\StepExecutionUpdated;
use App\Models\StepExecution;
use App\Models\User;
use App\States\StepExecution\Completed as StepCompleted;
use Illuminate\Support\Facades\DB;

class CompleteStep
{
    public function __construct(private AdvanceProcessInstance $advanceProcess) {}

    public function handle(StepExecution $step, User $user, array $data = []): void
    {
        if ($step->status->getValue() === 'completed') {
            return;
        }

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

            DB::afterCommit(function () use ($step) {
                event(new \App\Events\StepCompleted($step));
                event(new StepExecutionUpdated($step));
                $step->instance->refresh();
                event(new ProcessInstanceUpdated($step->instance));
            });
        });
    }
}
