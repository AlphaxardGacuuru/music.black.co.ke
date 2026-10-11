<?php

namespace App\Http\Controllers;

use App\Events\ChatDeletedEvent;
use App\Events\NewChatEvent;
use App\Http\Services\ChatService;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(protected ChatService $service) {}

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
            'to' => 'required|exists:users,username',
        ]);

        [$saved, $message, $chat] = $this->service->store($request);

        $user = User::where('username', $request->input('to'))->first();

        NewChatEvent::dispatchIf($saved, $chat, $user);

        return response([
            'message' => $message,
            'data' => $chat,
        ], 200);
    }

    /**
     * The conversation with the given username.
     */
    public function show(string $username)
    {
        return $this->service->show($username);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        [$deleted, $message] = $this->service->destroy($id);

        ChatDeletedEvent::dispatchIf($deleted, $id);

        return response(['message' => $message], 200);
    }
}
