<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepExecutionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instance_id' => $this->instance_id,
            'name' => $this->name,
            'description' => $this->step_snapshot_data['description'] ?? null,
            'order' => $this->order,
            'status' => $this->status->getValue(),
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->assignee?->full_name,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'deadline_at' => $this->deadline_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'completed_by' => $this->completed_by,
            'finisher_name' => $this->finisher?->full_name,
            'completion_notes' => $this->completion_notes,
            'messages' => $this->whenLoaded('messages', function () {
                return $this->messages->map(fn ($msg) => [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'sender_name' => $msg->sender?->full_name,
                    'is_manager' => $msg->sender_id === $this->instance->launched_by,
                    'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                ]);
            }),
        ];
    }
}
