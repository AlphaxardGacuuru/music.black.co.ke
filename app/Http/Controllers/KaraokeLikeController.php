<?php

namespace App\Http\Controllers;

use App\Http\Services\KaraokeLikeService;
use App\Models\KaraokeLike;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KaraokeLikeController extends Controller
{
    public function __construct(protected KaraokeLikeService $service)
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
    public function show(KaraokeLike $karaokeLike)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KaraokeLike $karaokeLike)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KaraokeLike $karaokeLike)
    {
        //
    }
}
