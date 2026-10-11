<?php

namespace App\Http\Controllers;

use App\Events\AudioCommentLikedEvent;
use App\Http\Services\AudioCommentLikeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AudioCommentLikeController extends Controller
{
    public function __construct(protected AudioCommentLikeService $service)
    {
        //
    }

    /**
     * Like or unlike an audio comment.
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'comment' => 'required|exists:audio_comments,id',
        ]);

        [$saved, $message, $audioCommentLike] = $this->service->store($request);

        AudioCommentLikedEvent::dispatchIf(
            $saved,
            $audioCommentLike->comment,
            $audioCommentLike->user
        );

        return response([
            'message' => $message,
            'data' => $audioCommentLike,
        ], 200);
    }
}
