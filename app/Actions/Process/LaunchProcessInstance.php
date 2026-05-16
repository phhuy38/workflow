<?php

namespace App\Actions\Process;

use App\Events\ProcessLaunched;
use App\Models\ProcessInstance;
use App\Models\ProcessTemplate;
use App\Models\StepExecution;
use App\Models\User;
use App\States\ProcessInstance\Running;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaunchProcessInstance
{
    public function __construct(private ResolveStepAssignee $resolveAssignee) {}

    public function handle(ProcessTemplate $template, array $data, User $launcher): ProcessInstance
    {
        if (! $template->is_published) {
            throw ValidationException::withMessages([
                'template_id' => 'Quy trình chưa được publish.',
            ]);
        }

        $template->load('stepDefinitions');

        return DB::transaction(function () use ($template, $data, $launcher) {
            // ADR-006: Snapshot template data
            $snapshot = $template->toArray();
            $snapshot['steps'] = $template->stepDefinitions->toArray();

            $instance = ProcessInstance::create([
                'template_id' => $template->id,
                'name' => $data['name'],
                'template_snapshot_data' => $snapshot,
                'context_data' => $data['context_data'] ?? [],
                'launched_by' => $launcher->id,
                'status' => 'pending', // Create in Pending first
            ]);

            // ADR-016: Explicit transition to Running
            $instance->status->transitionTo(Running::class);

            // ADR-005: Audit Log (Synchronous as per default config, explicit intent here)
            activity()
                ->performedOn($instance)
                ->causedBy($launcher)
                ->log('created');

            // AC1 & FR8: Create first StepExecution
            $firstStepDef = $template->stepDefinitions->sortBy('order')->first();
            if ($firstStepDef) {
                // AC8: Auto-assign first step (FR8)
                $firstStep = StepExecution::create([
                    'instance_id' => $instance->id,
                    'step_definition_id' => $firstStepDef->id,
                    'step_snapshot_data' => $firstStepDef->toArray(),
                    'name' => $firstStepDef->name,
                    'order' => $firstStepDef->order,
                    'status' => 'pending',
                    'assigned_to' => $this->resolveAssignee->handle($firstStepDef->toArray()),
                    'deadline_at' => now()->addHours($firstStepDef->duration_hours),
                ]);

                if ($firstStep->assigned_to) {
                    event(new \App\Events\TaskAssigned($firstStep));
                }
            }
            event(new ProcessLaunched($instance));

            return $instance;
        });
    }
}
