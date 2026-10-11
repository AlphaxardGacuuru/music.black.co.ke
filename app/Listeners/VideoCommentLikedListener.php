<?php

namespace App\Listeners;

use App\Events\VideoCommentLikedEvent;
use App\Notifications\VideoCommentLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VideoCommentLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VideoCommentLikedEvent $event): void
    {
        $event
            ->comment
            ->user
            ->notify(new VideoCommentLikedNotification($event->comment, $event->user));
    }
}
