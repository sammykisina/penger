<?php

namespace App\Mail;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Otp $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('auth.otp_mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
