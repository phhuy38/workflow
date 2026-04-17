<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'name' => $this->name,
            'description' => $this->description,
            'order' => $this->order,
            'assignee_type' => $this->assignee_type,
            'assignee_id' => $this->assignee_id,
            'duration_hours' => $this->duration_hours,
            'is_required' => $this->is_required,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
