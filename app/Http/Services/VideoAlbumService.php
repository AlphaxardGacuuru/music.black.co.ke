<?php

namespace App\Http\Services;

use App\Http\Resources\VideoAlbumResource;
use App\Models\User;
use App\Models\VideoAlbum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class VideoAlbumService extends Service
{
    // Get Video Albums
    public function index()
    {
        $getVideoAlbums = VideoAlbum::all();

        return VideoAlbumResource::collection($getVideoAlbums);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $getVideoAlbum = VideoAlbum::find($id);

        return new VideoAlbumResource($getVideoAlbum);
    }

    public function store($request)
    {
        /* Handle file upload */
        $vCover = $request->file('cover')->store('video-album-covers', 'public');

        /* Create new video album */
        $vAlbum = new VideoAlbum;
        $vAlbum->name = $request->input('name');
        $vAlbum->user_id = $this->id;
        $vAlbum->cover = $vCover;
        $vAlbum->released = $request->input('released');
        $saved = $vAlbum->save();

        $message = 'Video Album ' . $vAlbum->name . ' created';

        return [$saved, $message, $vAlbum];
    }

    public function update($request, $id)
    {
        $vAlbum = VideoAlbum::findOrFail($id);

        Gate::authorize('update', $vAlbum);

        /* Handle file upload */
        if ($request->hasFile('cover')) {
            $cover = $request->file('cover')->store('video-album-covers', 'public');

            // Delete the old cover, but never the shared default one
            $oldCover = $vAlbum->getRawOriginal('cover');

            if ($oldCover && $oldCover != 'video-album-covers/musical-note.png') {
                Storage::disk('public')->delete($oldCover);
            }

            $vAlbum->cover = $cover;
        }

        if ($request->filled('name')) {
            $vAlbum->name = $request->input('name');
        }

        if ($request->filled('released')) {
            $vAlbum->released = $request->input('released');
        }

        $saved = $vAlbum->save();

        $message = $vAlbum->name . ' edited';

        return [$saved, $message, $vAlbum];
    }

    /*
     * Artist's Video Albums
     */
    public function artistVideoAlbums($username)
    {
        $userId = User::where('username', $username)->value('id');

        $getArtistVideoAlbums = VideoAlbum::where('user_id', $userId)->get();

        return VideoAlbumResource::collection($getArtistVideoAlbums);
    }
}
