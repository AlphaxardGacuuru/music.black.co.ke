<?php

namespace App\Http\Controllers;

use App\Http\Resources\AudioAlbumResource;
use App\Http\Services\AudioAlbumService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AudioAlbumController extends Controller
{
    public function __construct(protected AudioAlbumService $service)
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
        $request->validate([
            'name' => 'required|string',
            'released' => 'required',
            'cover' => 'required|image|max:1999',
        ]);

        [$saved, $message, $audioAlbum] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $audioAlbum,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): AudioAlbumResource
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): Response
    {
        $request->validate([
            'cover' => 'nullable|image|max:1999',
        ]);

        [$saved, $message, $audioAlbum] = $this->service->update($request, $id);

        return response([
            'message' => $message,
            'data' => $audioAlbum,
        ], 200);
    }

    /*
     * Artist's Audio Albums */
    public function artistAudioAlbums(string $username): AnonymousResourceCollection
    {
        return $this->service->artistAudioAlbums($username);
    }
}
