<?php

namespace App\Notifications;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class BoughtVideoNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Video $video)
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
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * OLD wording kept as is: it names the artist, not the buyer.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => '/profile/show/'.$this->video->user->username,
            'from' => $this->video->user->username,
            'id' => $this->video->user->username,
            'message' => $this->video->user->username.' bought '.$this->video->name,
        ];
    }
}
