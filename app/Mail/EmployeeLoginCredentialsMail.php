<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeLoginCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $employee,
        public string $temporaryPassword,
        public User $sentBy,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your HRM Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-login-credentials',
            with: [
                'employeeName' => $this->employee->full_name,
                'employeeEmail' => $this->employee->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
                'sentByName' => $this->sentBy->full_name,
            ],
        );
    }
}
