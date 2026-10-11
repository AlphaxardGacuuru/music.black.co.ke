<?php

namespace App\Listeners;

use App\Events\AudioCommentLikedEvent;
use App\Notifications\AudioCommentLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AudioCommentLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AudioCommentLikedEvent $event): void
    {
        $event
            ->comment
            ->user
            ->notify(new AudioCommentLikedNotification($event->comment, $event->user));
    }
}
