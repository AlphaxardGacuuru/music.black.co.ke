<?php

namespace App\Http\Services;

use App\Models\KaraokeCommentLike;
use Illuminate\Http\Request;

class KaraokeCommentLikeService extends Service
{
    /**
     * Toggle the current user's like on a karaoke comment.
     */
    public function store(Request $request): array
    {
        $karaokeCommentLike = 'data';

        $query = KaraokeCommentLike::where('karaoke_comment_id', $request->input('comment'))
            ->where('user_id', $this->id);

        if ($query->exists()) {
            $query->delete();

            $message = 'Like removed';
            $added = false;
        } else {
            $karaokeCommentLike = new KaraokeCommentLike;
            $karaokeCommentLike->karaoke_comment_id = $request->input('comment');
            $karaokeCommentLike->user_id = $this->id;
            $karaokeCommentLike->save();

            $message = 'Comment liked';
            $added = true;
        }

        return [$added, $message, $karaokeCommentLike];
    }
}
