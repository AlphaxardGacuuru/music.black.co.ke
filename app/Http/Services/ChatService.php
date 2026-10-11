<?php

namespace App\Http\Services;

use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ChatService extends Service
{
    /**
     * The chat threads of the current user: the latest message with every
     * user they've chatted with.
     */
    public function index(): array
    {
        $getChat = Chat::where('user_id', $this->id)
            ->orWhere('to_id', $this->id)
            ->orderBy('id', 'DESC')
            ->get();

        $chatThreadsZero = [];
        $chatThreads = [];

        // Get sender and recipient
        foreach ($getChat as $chatItem) {
            $chatThreadsZero[] = $chatItem->user_id;
            $chatThreadsZero[] = $chatItem->to_id;
        }

        // Get only unique entries, without the current user
        $chatThreadsZero = array_diff(array_unique($chatThreadsZero), [$this->id]);

        // Get threads
        foreach ($chatThreadsZero as $userId) {
            $chat = $this->conversation($userId)
                ->orderBy('id', 'DESC')
                ->first();

            // Get user info
            $chatUser = User::find($userId);

            $chatThreads[] = [
                'id' => $chat->id,
                'avatar' => $chatUser->avatar,
                'name' => $chatUser->name,
                'username' => $chatUser->username,
                'to' => $chat->to?->username,
                'text' => $chat->text,
                'hasMedia' => $chat->media,
                'createdAt' => $chat->created_at,
            ];
        }

        return ['data' => $chatThreads];
    }

    /**
     * The conversation between the current user and the given username.
     */
    public function show(string $username): AnonymousResourceCollection
    {
        $userId = User::where('username', $username)->value('id');

        $getChat = $this->conversation($userId)
            ->orderBy('id', 'ASC')
            ->paginate(10);

        return ChatResource::collection($getChat);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        $chat = new Chat;
        $chat->user_id = $this->id;
        $chat->to_id = User::where('username', $request->input('to'))->value('id');
        $chat->text = $request->input('text');
        $chat->media = $request->input('media');
        $saved = $chat->save();

        $message = 'Chat sent';

        return [$saved, $message, $chat];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): array
    {
        $chatItem = Chat::findOrFail($id);

        Gate::authorize('delete', $chatItem);

        $media = $chatItem->getRawOriginal('media');

        if ($media) {
            Storage::disk('public')->delete($media);
        }

        $deleted = $chatItem->delete();

        return [$deleted, 'Chat deleted'];
    }

    /**
     * Messages exchanged between the current user and the given user.
     */
    protected function conversation(?string $userId)
    {
        return Chat::where(fn ($query) => $query->where('user_id', $this->id)->where('to_id', $userId))
            ->orWhere(fn ($query) => $query->where('user_id', $userId)->where('to_id', $this->id));
    }
}
