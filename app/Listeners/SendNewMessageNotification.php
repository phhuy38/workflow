<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Mail\NewMessageReceivedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewMessageNotification implements ShouldQueue
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

    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        if (! $message->recipient_id) {
            return;
        }

        try {
            $recipient = User::find($message->recipient_id);

            if ($recipient && $recipient->email) {
                Mail::to($recipient->email)->send(new NewMessageReceivedMail($message));
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send NewMessageReceivedMail to {$message->recipient_id}", ['exception' => $e]);
            throw $e;
        }
    }
}
