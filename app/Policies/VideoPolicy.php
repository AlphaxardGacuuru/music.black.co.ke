<?php

namespace App\Policies;

use App\Models\Video;
use App\Models\User;

class VideoPolicy extends OwnedByUserPolicy
{
    /**
     * Downloading the file is for whoever bought the song and its artist
     * (the admin is let through by before()).
     */
    public function download(User $user, Video $video): bool
    {
        return (string) $user->id === (string) $video->user_id
            || $video->hasBoughtVideo($user->id);
    }
}
