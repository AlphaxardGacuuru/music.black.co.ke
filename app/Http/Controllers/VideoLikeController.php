<?php

namespace App\Http\Controllers;

use App\Events\VideoLikedEvent;
use App\Http\Services\VideoLikeService;
use Illuminate\Http\Request;

class VideoLikeController extends Controller
{
    public function __construct(protected VideoLikeService $service)
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
            'video' => 'required|exists:videos,id',
        ]);

        [$canDispatch, $message, $videoLike] = $this->service->store($request);

        VideoLikedEvent::dispatchIf(
            $canDispatch,
            $videoLike->video,
            $videoLike->user
        );

        return response([
            'message' => $message,
            'data' => $videoLike,
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
