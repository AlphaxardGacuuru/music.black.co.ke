<?php

namespace App\Http\Controllers;

use App\Http\Services\BoughtVideoService;
use App\Models\BoughtVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoughtVideoController extends Controller
{
    public function __construct(protected BoughtVideoService $service)
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
    public function show(BoughtVideo $boughtVideo): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoughtVideo $boughtVideo): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoughtVideo $boughtVideo): void
    {
        //
    }

    /*
     * Artist's Bought Videos */
    public function artistBoughtVideos(string $username): AnonymousResourceCollection
    {
        return $this->service->artistBoughtVideos($username);
    }
}
