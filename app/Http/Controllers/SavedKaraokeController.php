<?php

namespace App\Http\Controllers;

use App\Http\Services\SavedKaraokeService;
use App\Models\SavedKaraoke;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SavedKaraokeController extends Controller
{
    public function __construct(protected SavedKaraokeService $service)
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
     * Store a newly created resource in storage (toggles the save).
     */
    public function store(Request $request): Response
    {
        $this->validate($request, [
            'id' => 'required|uuid',
        ]);

        [$saved, $message, $savedKaraoke] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $savedKaraoke,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(SavedKaraoke $savedKaraoke)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SavedKaraoke $savedKaraoke)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SavedKaraoke $savedKaraoke)
    {
        //
    }
}
