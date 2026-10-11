<?php

namespace App\Http\Services;

use App\Http\Resources\KaraokeCommentResource;
use App\Models\KaraokeComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class KaraokeCommentService extends Service
{
    /**
     * Display a listing of the resource (newest first).
     */
    public function index(): AnonymousResourceCollection
    {
        $getKaraokeComments = KaraokeComment::orderBy('created_at', 'DESC')->get();

        return KaraokeCommentResource::collection($getKaraokeComments);
    }

    /**
     * Display the comments of the given karaoke.
     */
    public function show(string $id): AnonymousResourceCollection
    {
        $getKaraokeComments = KaraokeComment::where('karaoke_id', $id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return KaraokeCommentResource::collection($getKaraokeComments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        $karaokeComment = new KaraokeComment;
        $karaokeComment->karaoke_id = $request->input('id');
        $karaokeComment->user_id = $this->id;
        $karaokeComment->text = $request->input('text');
        $saved = $karaokeComment->save();

        return [$saved, 'Comment posted', $karaokeComment];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): array
    {
        $comment = KaraokeComment::findOrFail($id);

        Gate::authorize('delete', $comment);

        $deleted = $comment->delete();

        return [$deleted, 'Comment deleted'];
    }
}
