<?php

namespace App\Http\Services;

use App\Http\Resources\PostCommentResource;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PostCommentService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getComments = PostComment::orderBy('id', 'DESC')->get();

        return PostCommentResource::collection($getComments);
    }

    /**
     * Display the comments of a post.
     */
    public function show(string $id): AnonymousResourceCollection
    {
        $getComments = PostComment::where('post_id', $id)
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return PostCommentResource::collection($getComments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        /* Create new comment */
        $postComment = new PostComment;
        $postComment->post_id = $request->input('id');
        $postComment->user_id = $this->id;
        $postComment->text = $request->input('text');
        $postComment->media = null;

        $saved = $postComment->save();

        return [$saved, 'Comment posted', $postComment];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): array
    {
        $comment = PostComment::findOrFail($id);

        Gate::authorize('delete', $comment);

        $deleted = $comment->delete();

        return [$deleted, 'Comment deleted'];
    }
}
