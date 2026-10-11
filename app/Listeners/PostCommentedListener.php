<?php

namespace App\Listeners;

use App\Events\PostCommentedEvent;
use App\Notifications\PostCommentedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostCommentedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostCommentedEvent $event): void
    {
        // Send Notification
        $event->post->user->notify(new PostCommentedNotification($event->post, $event->user));
    }
}
