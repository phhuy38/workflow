<?php

namespace App\Http\Resources;

use App\States\StepExecution\Completed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessInstanceResource extends JsonResource
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
            'template_id' => $this->template_id,
            'template_name' => $this->template->name,
            'name' => $this->name,
            'context_data' => $this->context_data,
            'status' => $this->status->getValue(),
            'launched_at' => $this->launched_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'launched_by' => $this->launched_by,
            'creator_name' => $this->creator->name,
            'progress' => $this->getProgress(),
            'current_step' => $this->getCurrentStepName(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getProgress(): int
    {
        $total = count($this->template_snapshot_data['steps'] ?? []);
        if ($total === 0) {
            return 0;
        }

        $completed = $this->stepExecutions
            ->filter(fn ($step) => in_array($step->status->getValue(), ['completed', 'skipped']))
            ->count();

        return (int) (($completed / $total) * 100);
    }

    private function getCurrentStepName(): string
    {
        $current = $this->stepExecutions
            ->filter(fn ($step) => in_array($step->status->getValue(), ['pending', 'in_progress']))
            ->sortBy('order')
            ->first();

        return $current ? $current->name : ($this->status->getValue() === 'completed' ? 'Đã hoàn thành' : 'N/A');
    }
}
