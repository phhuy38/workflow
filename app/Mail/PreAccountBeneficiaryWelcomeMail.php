<?php

namespace App\Mail;

use App\Models\ProcessInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreAccountBeneficiaryWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProcessInstance $instance)
    {
    }

    public function envelope(): Envelope
    {
        $cleanName = str_replace(["\r", "\n"], ' ', $this->instance->name);
        $subjectName = \Illuminate\Support\Str::limit($cleanName, 50);

        return new Envelope(
            subject: "Thông báo quy trình mới: {$subjectName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.beneficiary-welcome',
            with: [
                'instanceName' => $this->instance->name,
                'creatorName' => $this->instance->creator?->full_name ?? 'Hệ thống',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
