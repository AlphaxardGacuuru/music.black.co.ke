<?php

namespace App\Http\Services;

use App\Http\Resources\AudioCommentResource;
use App\Models\AudioComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AudioCommentService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getAudioComments = AudioComment::orderBy('created_at', 'DESC')->get();

        return AudioCommentResource::collection($getAudioComments);
    }

    /**
     * Display the comments of the specified audio.
     */
    public function show(string $id): AnonymousResourceCollection
    {
        $getAudioComments = AudioComment::where('audio_id', $id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return AudioCommentResource::collection($getAudioComments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array{0: bool, 1: string, 2: AudioComment}
     */
    public function store(Request $request): array
    {
        $audioComment = new AudioComment;
        $audioComment->audio_id = $request->input('id');
        $audioComment->user_id = $this->id;
        $audioComment->text = $request->input('text');

        $saved = $audioComment->save();
        // Check if commenter is owner of audio
        $notCurrentUser = $audioComment->audio->user_id != $this->id;
        // Dispatch if comment is saved successfully and commenter is not owner of audio
        $canDispatch = $notCurrentUser && $saved;

        return [$canDispatch, 'Comment posted', $audioComment];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return array{0: bool, 1: string}
     */
    public function destroy(string $id): array
    {
        $comment = AudioComment::findOrFail($id);

        Gate::authorize('delete', $comment);

        $deleted = $comment->delete();

        return [$deleted, 'Comment deleted'];
    }
}
