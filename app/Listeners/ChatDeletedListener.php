<?php

namespace App\Listeners;

use App\Events\ChatDeletedEvent;

class ChatDeletedListener
{
    /**
     * Handle the event.
     */
    public function handle(ChatDeletedEvent $event): void
    {
        //
    }
}
