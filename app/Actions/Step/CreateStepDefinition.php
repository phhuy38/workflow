<?php

namespace App\Actions\Step;

use App\Models\ProcessTemplate;
use App\Models\StepDefinition;

class CreateStepDefinition
{
    public function handle(ProcessTemplate $template, array $data): StepDefinition
    {
        $nextOrder = $template->stepDefinitions()->max('order') + 1;

        return $template->stepDefinitions()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'assignee_type' => $data['assignee_type'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'duration_hours' => $data['duration_hours'] ?? 24,
            'is_required' => $data['is_required'] ?? true,
            'order' => $nextOrder,
        ]);
    }
}
