<?php

namespace App\Http\Controllers;

use App\Http\Services\KaraokeCommentLikeService;
use App\Models\KaraokeCommentLike;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KaraokeCommentLikeController extends Controller
{
    public function __construct(protected KaraokeCommentLikeService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage (toggles the like).
     */
    public function store(Request $request): Response
    {
        [$saved, $message] = $this->service->store($request);

        return response([
            'message' => $message,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(KaraokeCommentLike $karaokeCommentLike)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KaraokeCommentLike $karaokeCommentLike)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KaraokeCommentLike $karaokeCommentLike)
    {
        //
    }
}
