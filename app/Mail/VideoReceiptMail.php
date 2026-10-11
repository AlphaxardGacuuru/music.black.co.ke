<?php

namespace App\Mail;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VideoReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  iterable<int, Video>  $videos
     */
    public function __construct(public $videos)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Video Receipt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.video-receipt',
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
