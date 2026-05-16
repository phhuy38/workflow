<?php

namespace App\Mail;

use App\Models\ProcessInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StateConsistencyAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProcessInstance $instance) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Hệ thống: Đã tự động đóng quy trình - {$this->instance->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.state-consistency-alert',
            with: [
                'url' => route('process-instances.show', $this->instance->id),
                'instanceName' => $this->instance->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
