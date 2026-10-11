<?php

namespace App\Http\Controllers;

use App\Events\StoryCreatedEvent;
use App\Http\Services\StoryService;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function __construct(protected StoryService $service) {}

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
            'media' => 'required|string',
            'text' => 'nullable|string',
        ]);

        [$saved, $message, $story] = $this->service->store($request);

        StoryCreatedEvent::dispatchIf($saved, $story);

        return response([
            'message' => $message,
            'data' => $story,
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        [$deleted, $message] = $this->service->destroy($id);

        return response(['message' => $message], 200);
    }

    /*
     * Seen */
    public function seen(string $id)
    {
        [$saved, $message, $story] = $this->service->seen($id);

        return response([
            'message' => $message,
            'data' => $story,
        ], 200);
    }

    /*
     * Mute */
    public function mute(string $username)
    {
        [$saved, $message, $story] = $this->service->mute($username);

        return response([
            'message' => $message,
            'data' => $story,
        ], 200);
    }
}
