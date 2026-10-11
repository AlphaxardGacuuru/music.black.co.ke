<?php

namespace App\Listeners;

use App\Events\FollowedEvent;
use App\Notifications\FollowedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class FollowedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(FollowedEvent $event): void
    {
        // OLD called "new FollowedNotification" without the follower, which
        // threw before anything was sent. The follower is now passed along.
        if ($event->follower) {
            $event->user->notify(new FollowedNotification($event->follower->username));
        }
    }
}
