<?php

namespace App\Http\Controllers;

use App\Events\PostedEvent;
use App\Http\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(protected PostService $service) {}

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

        [$saved, $message, $post] = $this->service->store($request);

        // Dispatch event
        PostedEvent::dispatchIf($saved, $post);

        return response([
            'message' => $message,
            'data' => $post,
        ], 200);
    }

    /**
     * Display the specified resource.
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
        [$saved, $message, $post] = $this->service->update($request, $id);

        return response([
            'message' => $message,
            'data' => $post,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        [$deleted, $message] = $this->service->destory($id);

        return response(['message' => $message], 200);
    }

    /*
     * Mute */
    public function mute(string $username)
    {
        [$saved, $message] = $this->service->mute($username);

        return response([
            'message' => $message,
        ], 200);
    }

    /*
     * Artist's Posts */
    public function artistPosts(string $username)
    {
        return $this->service->artistPosts($username);
    }
}
