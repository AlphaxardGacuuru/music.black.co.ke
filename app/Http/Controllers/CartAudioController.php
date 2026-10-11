<?php

namespace App\Http\Controllers;

use App\Http\Services\CartAudioService;
use App\Models\CartAudio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CartAudioController extends Controller
{
    public function __construct(protected CartAudioService $service)
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
     * Add the audio to the cart, or remove it if it is already there.
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'audio' => 'required|exists:audios,id',
        ]);

        [$saved, $message, $cartAudio] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $cartAudio,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(CartAudio $cartAudio): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartAudio $cartAudio): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartAudio $cartAudio): void
    {
        //
    }
}
