<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class InboxTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instance_id' => $this->instance_id,
            'name' => $this->name,
            'process_name' => $this->instance?->name,
            'template_name' => $this->instance?->template?->name,
            'status' => $this->status->getValue(),
            'urgency_status' => $this->getUrgencyStatus(),
            'deadline_at' => $this->deadline_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'launched_by_name' => $this->instance?->creator?->full_name,
        ];
    }

    private function getUrgencyStatus(): string
    {
        if (!$this->deadline_at || !$this->created_at) {
            return $this->status->getValue(); // Fallback
        }

        $now = now();
        $deadline = Carbon::parse($this->deadline_at);
        $createdAt = Carbon::parse($this->created_at);

        if ($now->greaterThan($deadline)) {
            return 'overdue';
        }

        $totalDurationMinutes = $createdAt->diffInMinutes($deadline);
        if ($totalDurationMinutes > 0) {
            $remainingMinutes = $now->diffInMinutes($deadline, false);
            $remainingPercentage = ($remainingMinutes / $totalDurationMinutes) * 100;

            if ($remainingPercentage <= 30) {
                return 'due_soon';
            }
        } elseif ($totalDurationMinutes <= 0 && $now->lessThanOrEqualTo($deadline)) {
            // If created and deadline are the same, and we are not overdue yet, it's due soon.
            return 'due_soon';
        }

        return $this->status->getValue();
    }
}
