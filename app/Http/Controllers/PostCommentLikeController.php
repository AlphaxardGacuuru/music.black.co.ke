<?php

namespace App\Http\Controllers;

use App\Events\PostCommentLikedEvent;
use App\Http\Services\PostCommentLikeService;
use Illuminate\Http\Request;

class PostCommentLikeController extends Controller
{
    public function __construct(protected PostCommentLikeService $service) {}

    /**
     * Like or unlike a post comment.
     */
    public function store(Request $request)
    {
        [$canDispatch, $message, $postCommentLike] = $this->service->store($request);

        PostCommentLikedEvent::dispatchIf(
            $canDispatch,
            $postCommentLike->comment,
            $postCommentLike->comment->post,
            $postCommentLike->user
        );

        return response([
            'message' => $message,
            'data' => $postCommentLike,
        ], 200);
    }
}
