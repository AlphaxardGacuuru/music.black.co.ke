<?php

namespace App\Listeners;

use App\Events\VideoBoughtEvent;
use App\Notifications\BoughtVideoNotification;
use App\Notifications\DecoNotification;
use App\Notifications\VideoReceiptNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VideoBoughtListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VideoBoughtEvent $event): void
    {
        // Notify Artist
        foreach ($event->videos as $video) {
            $video->user->notify(new BoughtVideoNotification($video));
        }

        // Notify Current user
        $event->user->notify(new VideoReceiptNotification($event->videos));

        /* Add deco notification */
        foreach ($event->decoArtists as $artist) {
            $event->user->notify(new DecoNotification($artist));
        }
    }
}
