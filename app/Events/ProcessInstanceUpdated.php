<?php

namespace App\Events;

use App\Models\ProcessInstance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessInstanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProcessInstance $instance) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('system.instances'),
            new PrivateChannel('instance.' . $this->instance->id),
        ];
    }
}
