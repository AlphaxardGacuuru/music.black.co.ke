<?php

namespace App\Http\Services;

use App\Models\VideoCommentLike;

class VideoCommentLikeService extends Service
{
    /**
     * Toggle the current user's like on a video comment.
     */
    public function store($request)
    {
        $query = VideoCommentLike::where('video_comment_id', $request->input('comment'))
            ->where('user_id', $this->id);

        $hasLiked = $query->exists();

        if ($hasLiked) {
            // Get Like
            $videoCommentLike = $query->first();
            // Delete
            $query->delete();
            // Set Message
            $message = 'Like removed';
            $added = false;
        } else {
            $videoCommentLike = new VideoCommentLike;
            $videoCommentLike->video_comment_id = $request->input('comment');
            $videoCommentLike->user_id = $this->id;
            $videoCommentLike->save();

            $message = 'Comment liked';
            $added = true;
        }

        // Check if current user is owner of the comment
        $notCurrentUser = $videoCommentLike->comment->user_id != $this->id;
        // Dispatch if like is saved successfully and current user is not owner of comment
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $videoCommentLike];
    }
}
