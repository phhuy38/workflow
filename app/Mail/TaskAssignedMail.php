<?php

namespace App\Mail;

use App\Models\StepExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StepExecution $step)
    {
    }

    public function envelope(): Envelope
    {
        $instanceName = $this->step->instance?->name ?? 'Unknown Process';
        return new Envelope(
            subject: "Bạn có công việc mới: {$this->step->name} - {$instanceName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.task-assigned',
            with: [
                'url' => route('process-instances.show', $this->step->instance_id),
                'stepName' => $this->step->name,
                'instanceName' => $this->step->instance?->name ?? 'Unknown Process',
                'deadline' => $this->step->deadline_at?->format('d/m/Y H:i'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
