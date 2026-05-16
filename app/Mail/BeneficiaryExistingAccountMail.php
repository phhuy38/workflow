<?php

namespace App\Mail;

use App\Models\ProcessInstance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryExistingAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ProcessInstance $instance)
    {
    }

    public function envelope(): Envelope
    {
        $cleanName = str_replace(["\r", "\n"], ' ', $this->instance->name);
        $subjectName = \Illuminate\Support\Str::limit($cleanName, 50);

        return new Envelope(
            subject: "Tiến độ mới: {$subjectName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.beneficiary-existing-account',
            with: [
                'loginUrl' => route('process-instances.show', $this->instance->id),
                'instanceName' => $this->instance->name,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
