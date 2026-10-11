<?php

namespace App\Listeners;

use App\Events\PostCommentLikedEvent;
use App\Notifications\PostCommentLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostCommentLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostCommentLikedEvent $event): void
    {
        $event->comment->user->notify(new PostCommentLikedNotification(
            $event->comment,
            $event->post,
            $event->user
        ));
    }
}
