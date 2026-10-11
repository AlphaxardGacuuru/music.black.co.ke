<?php

namespace App\Http\Services;

use App\Http\Resources\KaraokeResource;
use App\Models\Karaoke;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KaraokeService extends Service
{
    /**
     * Display a listing of the resource (oldest first, like OLD's id ASC).
     */
    public function index(): AnonymousResourceCollection
    {
        $getKaraokes = Karaoke::orderBy('created_at', 'ASC')->get();

        return KaraokeResource::collection($getKaraokes);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): KaraokeResource
    {
        $getKaraoke = Karaoke::findOrFail($id);

        return new KaraokeResource($getKaraoke);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): array
    {
        $karaoke = new Karaoke;
        $karaoke->karaoke = $request->input('karaoke');
        $karaoke->user_id = $this->id;
        $karaoke->audio_id = $request->input('audio_id');
        $karaoke->description = $request->input('description');
        $saved = $karaoke->save();

        return [$saved, 'Karaoke posted', $karaoke];
    }
}
