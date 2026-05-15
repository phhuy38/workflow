<?php

namespace App\Events;

use App\Models\ProcessInstance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessLaunched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProcessInstance $instance) {}

    public function broadcastOn(): array
    {
        // ADR-020: organization.{orgId} - For now, we don't have orgId, so using global org channel or similar
        // Since we are single-tenant as per ADR-001, we might use a fixed org ID or just 'organization'
        return [
            new PrivateChannel('organization.1'),
        ];
    }
}
