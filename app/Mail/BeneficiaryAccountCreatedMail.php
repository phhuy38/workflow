<?php

namespace App\Mail;

use App\Models\ProcessInstance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $password, public ProcessInstance $instance)
    {
    }

    public function envelope(): Envelope
    {
        $cleanName = str_replace(["\r", "\n"], ' ', $this->instance->name);
        $subjectName = \Illuminate\Support\Str::limit($cleanName, 50);

        return new Envelope(
            subject: "Tài khoản đăng nhập hệ thống: {$subjectName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.beneficiary-account-created',
            with: [
                'loginUrl' => route('login'),
                'email' => $this->user->email,
                'password' => $this->password,
                'instanceName' => $this->instance->name,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
