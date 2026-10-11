<?php

namespace App\Http\Controllers;

use App\Http\Services\KaraokeCommentService;
use App\Models\KaraokeComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KaraokeCommentController extends Controller
{
    public function __construct(protected KaraokeCommentService $service)
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
            'text' => 'required',
        ]);

        [$saved, $message, $karaokeComment] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $karaokeComment,
        ], 200);
    }

    /**
     * Display the specified resource (the comments of a karaoke).
     */
    public function show(string $id): AnonymousResourceCollection
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KaraokeComment $karaokeComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): Response
    {
        [$deleted, $message] = $this->service->destroy($id);

        return response(['message' => $message], 200);
    }
}
