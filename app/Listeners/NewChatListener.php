<?php

namespace App\Listeners;

use App\Events\NewChatEvent;
use App\Notifications\NewChatNotification;

class NewChatListener
{
    /**
     * Handle the event.
     */
    public function handle(NewChatEvent $event): void
    {
        $event->user->notify(new NewChatNotification($event->chat, $event->chat->user));
    }
}
