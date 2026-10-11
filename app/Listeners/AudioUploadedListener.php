<?php

namespace App\Listeners;

use App\Events\AudioUploadedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class AudioUploadedListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * OLD left this listener empty (and never dispatched the event), so
     * there is nothing to do yet.
     */
    public function handle(AudioUploadedEvent $event): void
    {
        //
    }
}
