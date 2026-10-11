<?php

namespace App\Http\Controllers;

use App\Events\VideoCommentedEvent;
use App\Http\Services\VideoCommentService;
use Illuminate\Http\Request;

class VideoCommentController extends Controller
{
    public function __construct(protected VideoCommentService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:videos,id',
            'text' => 'required',
        ]);

        [$canDispatch, $message, $videoComment] = $this->service->store($request);

        VideoCommentedEvent::dispatchIf(
            $canDispatch,
            $videoComment->video,
            $videoComment->user
        );

        return response([
            'message' => $message,
            'data' => $videoComment,
        ], 200);
    }

    /**
     * Display the specified resource (the comments of the given video).
     */
    public function show(string $id)
    {
        return $this->service->show($id);
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
        [$deleted, $message] = $this->service->destroy($id);

        return response(['message' => $message], 200);
    }
}
