<?php

namespace App\Policies;

use App\Models\Audio;
use App\Models\User;

class AudioPolicy extends OwnedByUserPolicy
{
    /**
     * Downloading the file is for whoever bought the song and its artist
     * (the admin is let through by before()).
     */
    public function download(User $user, Audio $audio): bool
    {
        return (string) $user->id === (string) $audio->user_id
            || $audio->hasBoughtAudio($user->id);
    }
}
