<?php

namespace App\Listeners;

use App\Events\StoryCreatedEvent;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\StoryCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class StoryCreatedListener implements ShouldQueue
{
    /**
     * Tell the poster's followers about their new story, except followers
     * who muted their stories (and the poster, who follows themselves).
     */
    public function handle(StoryCreatedEvent $event): void
    {
        $story = $event->story;

        $followerIds = Follow::query()
            ->where('followed_id', $story->user_id)
            ->where('user_id', '!=', $story->user_id)
            ->get()
            ->reject(fn(Follow $follow) => (bool) ($follow->muted['stories'] ?? false))
            ->pluck('user_id');

        Notification::send(
            User::query()->whereKey($followerIds)->get(),
            new StoryCreatedNotification($story, $story->user->username)
        );
    }
}
