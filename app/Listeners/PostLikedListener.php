<?php

namespace App\Listeners;

use App\Events\PostLikedEvent;
use App\Notifications\PostLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostLikedEvent $event): void
    {
        // Send Notification
        $event->post->user->notify(new PostLikedNotification($event->post, $event->user));
    }
}
