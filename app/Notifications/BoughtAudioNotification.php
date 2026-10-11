<?php

namespace App\Notifications;

use App\Models\Audio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class BoughtAudioNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Audio $audio)
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
            'url' => '/profile/show/'.$this->audio->user->username,
            'from' => $this->audio->user->username,
            'id' => $this->audio->user->username,
            'message' => $this->audio->user->username.' bought '.$this->audio->name,
        ];
    }
}
