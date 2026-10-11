<?php

namespace App\Listeners;

use App\Events\AudioLikedEvent;
use App\Notifications\AudioLikedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AudioLikedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AudioLikedEvent $event): void
    {
        $event
            ->audio
            ->user
            ->notify(new AudioLikedNotification($event->audio, $event->user));
    }
}
