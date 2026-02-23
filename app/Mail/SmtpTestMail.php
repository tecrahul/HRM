<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(private string $requestedBy)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SMTP Test Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.smtp-test',
            with: [
                'appName' => config('app.name'),
                'requestedBy' => $this->requestedBy,
            ],
        );
    }
}
