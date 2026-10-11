<?php

namespace App\Listeners;

use App\Events\VideoCommentedEvent;
use App\Notifications\VideoCommentedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VideoCommentedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VideoCommentedEvent $event): void
    {
        $event
            ->video
            ->user
            ->notify(new VideoCommentedNotification($event->video, $event->user));
    }
}
