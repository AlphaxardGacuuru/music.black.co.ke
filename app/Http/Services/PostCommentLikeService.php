<?php

namespace App\Http\Services;

use App\Models\PostCommentLike;
use Illuminate\Http\Request;

class PostCommentLikeService extends Service
{
    /**
     * Like or unlike a post comment.
     */
    public function store(Request $request): array
    {
        $query = PostCommentLike::where('post_comment_id', $request->input('comment'))
            ->where('user_id', $this->id);

        $hasLiked = $query->exists();

        if ($hasLiked) {
            // Get Like
            $postCommentLike = $query->first();
            // Delete Like
            $query->delete();
            // Set Message
            $message = 'Like removed';
            $added = false;
        } else {
            $postCommentLike = new PostCommentLike;
            $postCommentLike->post_comment_id = $request->input('comment');
            $postCommentLike->user_id = $this->id;
            $postCommentLike->save();

            $message = 'Comment liked';
            $added = true;
        }

        // Check if current user is owner of comment
        $notCurrentUser = $postCommentLike->comment->user_id != $this->id;
        // Dispatch if like is added and current user is not owner of comment
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $postCommentLike];
    }
}
