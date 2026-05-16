<?php

namespace App\Mail;

use App\Models\StepExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnacknowledgedStepAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StepExecution $step) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cần chú ý: Bước chưa được nhận việc - {$this->step->name}",
        );
    }

    public function content(): Content
    {
        $timeWaited = $this->step->created_at->diffForHumans(null, true);

        return new Content(
            markdown: 'emails.unacknowledged-step-alert',
            with: [
                'url' => route('process-instances.show', $this->step->instance_id),
                'stepName' => $this->step->name,
                'executorName' => $this->step->assignee?->full_name ?? 'N/A',
                'timeWaited' => $timeWaited,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
