<?php

namespace App\Http\Controllers;

use App\Events\PostLikedEvent;
use App\Http\Services\PostLikeService;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function __construct(protected PostLikeService $service) {}

    /**
     * Like or unlike a post.
     */
    public function store(Request $request)
    {
        [$canDispatch, $message, $postLike] = $this->service->store($request);

        PostLikedEvent::dispatchIf(
            $canDispatch,
            $postLike->post,
            $postLike->user
        );

        return response([
            'message' => $message,
            'data' => $postLike,
        ], 200);
    }
}
