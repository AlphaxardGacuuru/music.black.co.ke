<?php

namespace App\Http\Controllers;

use App\Events\AudioLikedEvent;
use App\Http\Services\AudioLikeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AudioLikeController extends Controller
{
    public function __construct(protected AudioLikeService $service)
    {
        //
    }

    /**
     * Like or unlike an audio.
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'audio' => 'required|exists:audios,id',
        ]);

        [$canDispatch, $message, $audioLike] = $this->service->store($request);

        AudioLikedEvent::dispatchIf(
            $canDispatch,
            $audioLike->audio,
            $audioLike->user
        );

        return response([
            'message' => $message,
            'data' => $audioLike,
        ], 200);
    }
}
