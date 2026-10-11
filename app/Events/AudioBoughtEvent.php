<?php

namespace App\Events;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioBoughtEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  mixed  $structuredAudios  BoughtAudioResource collection for the receipt
     * @param  array<int, Audio>  $audios  the audios just bought
     * @param  array<int, string>  $decoArtists  usernames of artists that decorated the buyer
     * @param  User  $user  the buyer
     */
    public function __construct(
        public $structuredAudios,
        public array $audios,
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
        return new PrivateChannel('audio-bought');
    }
}
