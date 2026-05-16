<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Mail\TaskAssignedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public bool $deleteWhenMissingModels = true;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function viaConnection(): string
    {
        return config('queue.default');
    }

    public function viaQueue(): string
    {
        return 'notifications';
    }

    public function handle(TaskAssigned $event): void
    {
        $event->step->loadMissing(['assignee', 'instance']);

        if ($event->step->status->getValue() !== 'pending') {
            return;
        }

        if (! $event->step->assignee) {
            return;
        }

        try {
            Mail::to($event->step->assignee->email)->send(new TaskAssignedMail($event->step));
        } catch (Throwable $e) {
            Log::error("Failed to send TaskAssigned email to {$event->step->assignee->email}", ['exception' => $e]);
            throw $e;
        }
    }
}
