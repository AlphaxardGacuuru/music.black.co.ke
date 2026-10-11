<?php

namespace App\Notifications;

use App\Models\AudioComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class AudioCommentLikedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AudioComment $comment,
        public User $user,
    ) {}

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
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url' => '/audio/show/' . $this->comment->audio->id,
            'from' => $this->user->username,
            'message' => $this->user->username . ' liked your comment on ' . $this->comment->audio->name,
        ];
    }
}
