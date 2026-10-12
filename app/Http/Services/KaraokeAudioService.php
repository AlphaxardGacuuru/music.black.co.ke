<?php

namespace App\Http\Services;

use App\Http\Resources\KaraokeAudioResource;
use App\Models\KaraokeAudio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class KaraokeAudioService extends Service
{
    /**
     * Display a listing of the resource, with each song's name and thumbnail
     * (the raw rows only carry audio_id).
     */
    public function index(): Collection
    {
        return KaraokeAudio::with('audio:id,name,thumbnail')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): KaraokeAudioResource
    {
        $getKaraokeAudio = KaraokeAudio::findOrFail($id);

        return new KaraokeAudioResource($getKaraokeAudio);
    }

    /**
     * Store Karaoke Audio.
     */
    public function store(Request $request): array
    {
        $karaokeAudio = new KaraokeAudio;
        $karaokeAudio->audio_id = $request->input('audio_id');
        $karaokeAudio->user_id = $this->id;
        $saved = $karaokeAudio->save();

        return [$saved, 'Audio saved', $karaokeAudio];
    }
}
