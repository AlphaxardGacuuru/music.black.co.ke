<?php

namespace App\Http\Controllers;

use App\Http\Services\CartVideoService;
use App\Models\CartVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CartVideoController extends Controller
{
    public function __construct(protected CartVideoService $service)
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
     * Add the video to the cart, or remove it if it is already there.
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'video' => 'required|exists:videos,id',
        ]);

        [$saved, $message, $cartVideo] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $cartVideo,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(CartVideo $cartVideo): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartVideo $cartVideo): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartVideo $cartVideo): void
    {
        //
    }
}
