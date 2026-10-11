<?php

namespace App\Http\Controllers;

use App\Http\Resources\KaraokeResource;
use App\Http\Services\KaraokeService;
use App\Models\Karaoke;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KaraokeController extends Controller
{
    public function __construct(protected KaraokeService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $this->validate($request, [
            'karaoke' => 'required|string',
            'audio_id' => 'required',
            'description' => 'required|string',
        ]);

        [$saved, $message, $karaoke] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $karaoke,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): KaraokeResource
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Karaoke $karaoke)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Karaoke $karaoke)
    {
        //
    }
}
