<?php

namespace App\Listeners;

use App\Events\VideoLikedEvent;
use App\Notifications\VideoLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VideoLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VideoLikedEvent $event): void
    {
        $event
            ->video
            ->user
            ->notify(new VideoLikedNotification($event->video, $event->user));
    }
}
