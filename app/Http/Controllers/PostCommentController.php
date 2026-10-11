<?php

namespace App\Http\Controllers;

use App\Events\PostCommentedEvent;
use App\Http\Services\PostCommentService;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function __construct(protected PostCommentService $service) {}

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
            'text' => 'required',
        ]);

        [$saved, $message, $postComment] = $this->service->store($request);

        PostCommentedEvent::dispatchIf(
            $saved,
            $postComment,
            $postComment->post,
            $postComment->user
        );

        return response([
            'message' => $message,
            'data' => $postComment,
        ], 200);
    }

    /**
     * Display the comments of the given post.
     */
    public function show(string $id)
    {
        return $this->service->show($id);
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
