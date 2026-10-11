<?php

namespace App\Http\Services;

use App\Http\Resources\KaraokeAudioResource;
use App\Models\KaraokeAudio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class KaraokeAudioService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Collection
    {
        return KaraokeAudio::all();
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
