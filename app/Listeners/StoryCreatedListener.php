<?php

namespace App\Listeners;

use App\Events\StoryCreatedEvent;

class StoryCreatedListener
{
    /**
     * Handle the event.
     */
    public function handle(StoryCreatedEvent $event): void
    {
        // OLD only instantiated a StoryCreatedNotification (with a missing
        // argument) and never sent it to anyone, so no notification goes out.
        // StoryCreatedNotification is kept ready for when recipients are decided.
    }
}
