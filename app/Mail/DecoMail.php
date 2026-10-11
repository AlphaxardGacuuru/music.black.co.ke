<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DecoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $username  the decorated fan
     * @param  string  $artist  the artist's username
     */
    public function __construct(public string $username, public string $artist)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Deco',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.deco',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
