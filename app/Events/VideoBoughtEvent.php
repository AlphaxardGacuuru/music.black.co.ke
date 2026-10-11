<?php

namespace App\Events;

use App\Models\User;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoBoughtEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  mixed  $structuredVideos  BoughtVideoResource collection for the receipt
     * @param  array<int, Video>  $videos  the videos just bought
     * @param  array<int, string>  $decoArtists  usernames of artists that decorated the buyer
     * @param  User  $user  the buyer
     */
    public function __construct(
        public $structuredVideos,
        public array $videos,
        public array $decoArtists,
        public User $user,
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('video-bought');
    }
}
