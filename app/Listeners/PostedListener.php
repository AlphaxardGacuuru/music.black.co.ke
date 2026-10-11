<?php

namespace App\Listeners;

use App\Events\PostedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostedEvent $event): void
    {
        //
    }
}
