<?php

namespace App\Http\Resources;

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
            'creator_name' => $this->creator ? $this->creator->full_name : 'Hệ thống',
            'progress' => $this->getProgress(),
            'current_step' => $this->getCurrentStepName(),
            'traffic_light_status' => \App\Services\InstanceStatusCalculator::calculate($this->resource),
            'time_elapsed' => $this->getTimeElapsed(),
            'estimated_remaining_hours' => $this->getEstimatedRemainingHours(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getTimeElapsed(): string
    {
        if (!$this->launched_at) {
            return 'N/A';
        }

        $end = $this->completed_at ?? now();
        $diff = $this->launched_at->diff($end);

        $parts = [];
        if ($diff->d > 0) $parts[] = $diff->d . ' ' . __('days');
        if ($diff->h > 0) $parts[] = $diff->h . ' ' . __('hours');
        if ($diff->i > 0) $parts[] = $diff->i . ' ' . __('minutes');

        return count($parts) > 0 ? implode(' ', $parts) : __('Under 1 minute');
    }

    private function getEstimatedRemainingHours(): int
    {
        $completedOrder = $this->stepExecutions
            ->filter(fn ($step) => in_array($step->status->getValue(), ['completed', 'skipped']))
            ->max('order') ?? 0;

        return collect($this->template_snapshot_data['steps'] ?? [])
            ->filter(fn ($stepDef) => ($stepDef['order'] ?? 0) > $completedOrder)
            ->sum('duration_hours');
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
