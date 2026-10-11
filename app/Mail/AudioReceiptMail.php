<?php

namespace App\Mail;

use App\Models\Audio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AudioReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  iterable<int, Audio>  $audios
     */
    public function __construct(public $audios)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Audio Receipt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.audio-receipt',
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
