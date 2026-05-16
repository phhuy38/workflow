<?php

namespace App\Mail;

use App\Models\StepExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApproachingDeadlineAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StepExecution $step) {}

    public function envelope(): Envelope
    {
        $isOverdue = $this->step->deadline_at->isPast();
        $statusPrefix = $isOverdue ? 'Đã quá hạn' : 'Sắp đến hạn';

        return new Envelope(
            subject: "{$statusPrefix}: {$this->step->name} - {$this->step->instance->name}",
        );
    }

    public function content(): Content
    {
        $isOverdue = $this->step->deadline_at->isPast();

        return new Content(
            markdown: 'emails.approaching-deadline-alert',
            with: [
                'url' => route('process-instances.show', $this->step->instance_id),
                'stepName' => $this->step->name,
                'instanceName' => $this->step->instance->name,
                'deadline' => $this->step->deadline_at->format('d/m/Y H:i'),
                'isOverdue' => $isOverdue,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
