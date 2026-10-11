<?php

namespace App\Http\Services;

use App\Models\AudioCommentLike;
use Illuminate\Http\Request;

class AudioCommentLikeService extends Service
{
    /**
     * Like or unlike an audio comment.
     *
     * @return array{0: bool, 1: string, 2: AudioCommentLike}
     */
    public function store(Request $request): array
    {
        $query = AudioCommentLike::where('audio_comment_id', $request->input('comment'))
            ->where('user_id', $this->id);

        $audioCommentLike = $query->first();

        if ($audioCommentLike) {
            // Delete Like
            $query->delete();

            $message = 'Like removed';
            $added = false;
        } else {
            $audioCommentLike = new AudioCommentLike;
            $audioCommentLike->audio_comment_id = $request->input('comment');
            $audioCommentLike->user_id = $this->id;
            $audioCommentLike->save();

            $message = 'Comment liked';
            $added = true;
        }

        // Check if user is owner of audio comment
        $notCurrentUser = $audioCommentLike->comment->user_id != $this->id;
        // Dispatch if like is saved successfully and user is not owner of audio comment
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $audioCommentLike];
    }
}
