<?php

namespace App\Http\Services;

use App\Http\Resources\AudioAlbumResource;
use App\Models\AudioAlbum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class AudioAlbumService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getAudioAlbums = AudioAlbum::all();

        return AudioAlbumResource::collection($getAudioAlbums);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): AudioAlbumResource
    {
        $audioAlbum = AudioAlbum::findOrFail($id);

        return new AudioAlbumResource($audioAlbum);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return array{0: bool, 1: string, 2: AudioAlbum}
     */
    public function store(Request $request): array
    {
        /* Handle file upload */
        $aCover = $request->file('cover')->store('audio-album-covers', 'public');

        /* Create new audio album */
        $aAlbum = new AudioAlbum;
        $aAlbum->name = $request->input('name');
        $aAlbum->user_id = $this->id;
        $aAlbum->cover = $aCover;
        $aAlbum->released = $request->input('released');
        $saved = $aAlbum->save();

        $message = 'Audio Album ' . $aAlbum->name . ' created.';

        return [$saved, $message, $aAlbum];
    }

    /**
     * Update the specified resource in storage.
     *
     * @return array{0: bool, 1: string, 2: AudioAlbum}
     */
    public function update(Request $request, string $id): array
    {
        $aAlbum = AudioAlbum::findOrFail($id);

        Gate::authorize('update', $aAlbum);

        if ($request->filled('name')) {
            $aAlbum->name = $request->input('name');
        }

        /* Handle file upload */
        if ($request->hasFile('cover')) {
            $cover = $request->file('cover')->store('audio-album-covers', 'public');

            // Delete old cover
            Storage::disk('public')->delete($aAlbum->getRawOriginal('cover'));

            $aAlbum->cover = $cover;
        }

        if ($request->filled('released')) {
            $aAlbum->released = $request->input('released');
        }

        $saved = $aAlbum->save();

        $message = $aAlbum->name . ' updated';

        return [$saved, $message, $aAlbum];
    }

    /*
     * Artist's Audio Albums */
    public function artistAudioAlbums(string $username): AnonymousResourceCollection
    {
        $userId = User::where('username', $username)->value('id');

        $getArtistAudioAlbums = AudioAlbum::where('user_id', $userId)->get();

        return AudioAlbumResource::collection($getArtistAudioAlbums);
    }
}
