<?php

namespace App\Listeners;

use App\Events\AudioBoughtEvent;
use App\Notifications\AudioReceiptNotification;
use App\Notifications\BoughtAudioNotification;
use App\Notifications\DecoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AudioBoughtListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(AudioBoughtEvent $event): void
    {
        // Notify Artist
        foreach ($event->audios as $audio) {
            $audio->user->notify(new BoughtAudioNotification($audio));
        }

        // Notify Current user
        $event->user->notify(new AudioReceiptNotification($event->audios));

        /* Add deco notification */
        foreach ($event->decoArtists as $artist) {
            $event->user->notify(new DecoNotification($artist));
        }
    }
}
