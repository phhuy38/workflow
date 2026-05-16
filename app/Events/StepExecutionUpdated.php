<?php

namespace App\Events;

use App\Models\StepExecution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepExecutionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public StepExecution $step) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('instance.' . $this->step->instance_id),
        ];

        if ($this->step->assigned_to) {
            $channels[] = new PrivateChannel('user.' . $this->step->assigned_to);
        }

        return $channels;
    }
}
