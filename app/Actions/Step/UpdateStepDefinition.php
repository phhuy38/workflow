<?php

namespace App\Actions\Step;

use App\Models\StepDefinition;

class UpdateStepDefinition
{
    public function handle(StepDefinition $step, array $data): StepDefinition
    {
        $step->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'assignee_type' => $data['assignee_type'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'duration_hours' => $data['duration_hours'],
            'is_required' => $data['is_required'],
        ]);

        return $step->fresh();
    }
}
