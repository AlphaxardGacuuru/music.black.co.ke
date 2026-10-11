<?php

namespace App\Http\Controllers;

use App\Events\VideoCommentLikedEvent;
use App\Http\Services\VideoCommentLikeService;
use Illuminate\Http\Request;

class VideoCommentLikeController extends Controller
{
    public function __construct(protected VideoCommentLikeService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'comment' => 'required|exists:video_comments,id',
        ]);

        [$canDispatch, $message, $videoCommentLike] = $this->service->store($request);

        VideoCommentLikedEvent::dispatchIf(
            $canDispatch,
            $videoCommentLike->comment,
            $videoCommentLike->user
        );

        return response([
            'message' => $message,
            'data' => $videoCommentLike,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
