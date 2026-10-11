<?php

namespace App\Notifications;

use App\Mail\DecoMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class DecoNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * @param  string  $artist  the username of the artist that decorated the user
     */
    public function __construct(public string $artist)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): DecoMail
    {
        return (new DecoMail($notifiable->username, $this->artist))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = 'Congratulations! '.$this->artist.' decorated you.';

        return [
            'url' => '/profile/show/'.$this->artist,
            'from' => $this->artist,
            'message' => $message,
        ];
    }
}
