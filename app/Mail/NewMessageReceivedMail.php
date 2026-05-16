<?php

namespace App\Mail;

use App\Models\StepMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StepMessage $message)
    {
        $this->message->loadMissing(['sender', 'stepExecution.instance']);
    }

    public function envelope(): Envelope
    {
        $instanceName = $this->message->stepExecution->instance->name;
        $cleanName = str_replace(["\r", "\n"], ' ', $instanceName);
        $subjectName = Str::limit($cleanName, 50);

        return new Envelope(
            subject: "Bạn có tin nhắn mới trong quy trình: {$subjectName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-message',
            with: [
                'url' => route('process-instances.show', $this->message->stepExecution->instance_id),
                'senderName' => $this->message->sender?->full_name ?? 'Người dùng',
                'messageBody' => $this->message->body,
                'stepName' => $this->message->stepExecution->name,
                'instanceName' => $this->message->stepExecution->instance->name,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
