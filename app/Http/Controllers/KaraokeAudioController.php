<?php

namespace App\Http\Controllers;

use App\Http\Resources\KaraokeAudioResource;
use App\Http\Services\KaraokeAudioService;
use App\Models\KaraokeAudio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KaraokeAudioController extends Controller
{
    public function __construct(protected KaraokeAudioService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Collection
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $this->validate($request, [
            'audio_id' => 'required|exists:audios,id',
        ]);

        [$saved, $message, $karaokeAudio] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $karaokeAudio,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): KaraokeAudioResource
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KaraokeAudio $karaokeAudio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KaraokeAudio $karaokeAudio)
    {
        //
    }
}
