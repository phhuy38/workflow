<?php

namespace App\Actions\Process;

use App\Models\StepExecution;
use App\Models\StepMessage;
use App\Models\User;

class SendMessageToStep
{
    public function handle(StepExecution $step, User $sender, string $body): StepMessage
    {
        $recipientId = null;

        if ($sender->id === $step->assigned_to) {
            // Executor replying
            $recipientId = $step->instance->created_for ?? $step->instance->launched_by;
        } elseif ($sender->hasRole('beneficiary') && $step->instance->created_for === $sender->id) {
            $recipientId = $step->assigned_to;
        } else {
            // Manager/Admin pinging
            $recipientId = $step->assigned_to;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($step, $sender, $recipientId, $body) {
            $message = StepMessage::create([
                'step_execution_id' => $step->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipientId,
                'body' => $body,
            ]);

            // ADR-005: Audit Log
            activity()
                ->performedOn($step)
                ->causedBy($sender)
                ->withProperties(['message_id' => $message->id])
                ->log('sent_message');

            \Illuminate\Support\Facades\DB::afterCommit(function () use ($message, $step) {
                // Fire new message event for email
                event(new \App\Events\MessageSent($message));

                // Optional: Trigger StepExecutionUpdated to notify frontend via Echo
                event(new \App\Events\StepExecutionUpdated($step));
            });

            return $message;
        });
    }
}
