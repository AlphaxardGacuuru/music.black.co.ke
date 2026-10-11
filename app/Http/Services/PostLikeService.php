<?php

namespace App\Http\Services;

use App\Models\PostLike;
use Illuminate\Http\Request;

class PostLikeService extends Service
{
    /**
     * Like or unlike a post.
     */
    public function store(Request $request): array
    {
        $query = PostLike::where('post_id', $request->input('post'))
            ->where('user_id', $this->id);

        $hasLiked = $query->exists();

        if ($hasLiked) {
            // Get like
            $postLike = $query->first();
            // Delete
            $query->delete();
            // Set Message
            $message = 'Like removed';
            $added = false;
        } else {
            $postLike = new PostLike;
            $postLike->post_id = $request->input('post');
            $postLike->user_id = $this->id;
            $postLike->save();

            $message = 'Post liked';
            $added = true;
        }

        // Check if current user is owner of post
        $notCurrentUser = $postLike->post->user_id != $this->id;
        // Dispatch if like is added and current user is not owner of post
        $canDispatch = $notCurrentUser && $added;

        return [$canDispatch, $message, $postLike];
    }
}
