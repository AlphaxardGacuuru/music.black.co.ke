<?php

namespace App\Http\Controllers;

use App\Http\Services\BoughtAudioService;
use App\Models\BoughtAudio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoughtAudioController extends Controller
{
    public function __construct(protected BoughtAudioService $service)
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
     *
     * Buying happens when an M-Pesa payment lands (see BuyCartListener),
     * this endpoint did nothing in OLD either.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BoughtAudio $boughtAudio): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoughtAudio $boughtAudio): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoughtAudio $boughtAudio): void
    {
        //
    }

    /*
     * Artist's Bought Audios */
    public function artistBoughtAudios(string $username): AnonymousResourceCollection
    {
        return $this->service->artistBoughtAudios($username);
    }
}
