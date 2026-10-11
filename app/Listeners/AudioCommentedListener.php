<?php

namespace App\Listeners;

use App\Events\AudioCommentedEvent;
use App\Notifications\AudioCommentedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AudioCommentedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AudioCommentedEvent $event): void
    {
        $event
            ->audio
            ->user
            ->notify(new AudioCommentedNotification($event->audio, $event->user));
    }
}
