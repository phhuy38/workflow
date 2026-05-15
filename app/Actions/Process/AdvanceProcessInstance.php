<?php

namespace App\Actions\Process;

use App\Models\ProcessInstance;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Completed as ProcessCompleted;
use Illuminate\Support\Facades\DB;

class AdvanceProcessInstance
{
    public function __construct(private ResolveStepAssignee $resolveAssignee)
    {
    }

    public function handle(ProcessInstance $instance, StepExecution $currentStep, User $user): void
    {
        $stepsSnapshot = collect($instance->template_snapshot_data['steps'] ?? []);

        $nextStepDef = $stepsSnapshot
            ->where('order', '>', $currentStep->order)
            ->sortBy('order')
            ->first();

        if ($nextStepDef) {
            // AC3: Create next StepExecution (FR12)
            StepExecution::create([
                'instance_id' => $instance->id,
                'step_definition_id' => $nextStepDef['id'],
                'step_snapshot_data' => $nextStepDef,
                'name' => $nextStepDef['name'],
                'order' => $nextStepDef['order'],
                'status' => 'pending',
                'assigned_to' => $this->resolveAssignee->handle($nextStepDef),
                'deadline_at' => now()->addHours($nextStepDef['duration_hours']),
            ]);

            // Optionally fire an event/notification here in future stories
        } else {
            // AC3: No more steps, complete the process
            $instance->status->transitionTo(ProcessCompleted::class);
            $instance->update([
                'completed_at' => now(),
            ]);

            activity()
                ->performedOn($instance)
                ->causedBy($user)
                ->log('completed');
        }
    }
}
