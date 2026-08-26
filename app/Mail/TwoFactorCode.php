<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sprint 68 — Email OTP for teacher two-factor authentication.
 * The plain code only ever exists inside this email; it is stored
 * hashed in cache and never logged.
 */
class TwoFactorCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $ttlMinutes = 10)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'كود التحقق — منصة بيان');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.two-factor-code');
    }
}