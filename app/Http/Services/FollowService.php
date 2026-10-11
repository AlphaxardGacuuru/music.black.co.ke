<?php

namespace App\Http\Services;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;

class FollowService extends Service
{
    /**
     * Follow or unfollow a musician. Only fans who bought at least one of
     * the musician's songs (or @blackmusic itself) may follow.
     */
    public function store(Request $request): array
    {
        $follow = 'data';

        $musicianUsername = $request->input('musician');

        $musician = User::where('username', $musicianUsername)->firstOrFail();

        $isBlackMusic = auth('sanctum')->user()?->username == '@blackmusic';

        // Check if user has bought video or audio
        if ($musician->hasBought1($this->id) || $isBlackMusic) {
            // Check if user has followed
            $query = Follow::where('followed_id', $musician->id)
                ->where('user_id', $this->id);

            if ($query->exists()) {
                $query->delete();

                $message = 'You Unfollowed '.$musicianUsername;
                $added = false;
            } else {
                $follow = new Follow;
                $follow->followed_id = $musician->id;
                $follow->user_id = $this->id;
                $follow->muted = ['posts' => false, 'stories' => false];
                $follow->save();

                $message = 'You Followed '.$musicianUsername;
                $added = true;
            }

            return [$added, $message, $follow];
        }

        $message = 'You must have bought atleast one song by '.$musicianUsername;

        return [false, $message, $follow];
    }
}
