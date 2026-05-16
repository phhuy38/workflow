<?php

namespace App\Mail;

use App\Models\ProcessInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryAccountCreationFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProcessInstance $instance, public string $beneficiaryEmail) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Lỗi: Không thể tạo tài khoản cho người thụ hưởng trong quy trình {$this->instance->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.beneficiary-account-creation-failed',
            with: [
                'instanceName' => $this->instance->name,
                'beneficiaryEmail' => $this->beneficiaryEmail,
                'url' => route('process-instances.show', $this->instance->id),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
