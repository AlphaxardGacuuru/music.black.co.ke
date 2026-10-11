<?php

namespace App\Http\Services;

use App\Models\VideoLike;

class VideoLikeService extends Service
{
    /**
     * Toggle the current user's like on a video.
     */
    public function store($request)
    {
        $query = VideoLike::where('video_id', $request->input('video'))
            ->where('user_id', $this->id);

        $hasLiked = $query->exists();

        if ($hasLiked) {
            // Get Like
            $videoLike = $query->first();
            // Delete Like
            $query->delete();
            // Set Message
            $message = 'Like removed';
            $added = false;
        } else {
            $videoLike = new VideoLike;
            $videoLike->video_id = $request->input('video');
            $videoLike->user_id = $this->id;
            $videoLike->save();

            $message = 'Video liked';
            $added = true;
        }

        // Check if user is owner of video
        $notCurrentUser = $videoLike->video->user_id != $this->id;
        // Dispatch if like is saved successfully and user is not owner of video
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $videoLike];
    }
}
