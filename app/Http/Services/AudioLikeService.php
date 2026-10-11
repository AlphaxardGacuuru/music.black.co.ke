<?php

namespace App\Http\Services;

use App\Models\AudioLike;
use Illuminate\Http\Request;

class AudioLikeService extends Service
{
    /**
     * Like or unlike an audio.
     *
     * @return array{0: bool, 1: string, 2: AudioLike}
     */
    public function store(Request $request): array
    {
        $query = AudioLike::where('audio_id', $request->input('audio'))
            ->where('user_id', $this->id);

        $audioLike = $query->first();

        if ($audioLike) {
            // Delete Like
            $query->delete();

            $message = 'Like removed';
            $added = false;
        } else {
            $audioLike = new AudioLike;
            $audioLike->audio_id = $request->input('audio');
            $audioLike->user_id = $this->id;
            $audioLike->save();

            $message = 'Audio liked';
            $added = true;
        }

        // Check if user is owner of audio
        $notCurrentUser = $audioLike->audio->user_id != $this->id;
        // Dispatch if like is saved successfully and user is not owner of audio
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $audioLike];
    }
}
