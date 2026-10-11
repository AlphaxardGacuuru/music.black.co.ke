<?php

namespace App\Http\Services;

use App\Http\Resources\VideoCommentResource;
use App\Models\VideoComment;
use Illuminate\Support\Facades\Gate;

class VideoCommentService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $getVideoComments = VideoComment::orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        return VideoCommentResource::collection($getVideoComments);
    }

    /**
     * Display the comments of the given video.
     */
    public function show($id)
    {
        $getVideoComments = VideoComment::where('video_id', $id)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return VideoCommentResource::collection($getVideoComments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {
        /* Create new comment */
        $videoComment = new VideoComment;
        $videoComment->video_id = $request->input('id');
        $videoComment->user_id = $this->id;
        $videoComment->text = $request->input('text');

        $saved = $videoComment->save();
        // Check if commenter is owner of video
        $notCurrentUser = $videoComment->video->user_id != $this->id;
        // Dispatch if comment is saved successfully and commenter is not owner of video
        $canDispatch = $notCurrentUser && $saved;

        return [$canDispatch, 'Comment posted', $videoComment];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $comment = VideoComment::findOrFail($id);

        Gate::authorize('delete', $comment);

        $deleted = $comment->delete();

        return [$deleted, 'Comment deleted'];
    }
}
