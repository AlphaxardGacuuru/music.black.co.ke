<?php

namespace App\Http\Controllers;

use App\Http\Services\VideoAlbumService;
use Illuminate\Http\Request;

class VideoAlbumController extends Controller
{
    public function __construct(protected VideoAlbumService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cover' => 'required|image|max:1999',
        ]);

        [$saved, $message, $videoAlbum] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $videoAlbum,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'cover' => 'nullable|image|max:1999',
        ]);

        [$saved, $message, $videoAlbum] = $this->service->update($request, $id);

        return response([
            'message' => $message,
            'data' => $videoAlbum,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /*
     * Artist's Video Albums
     */
    public function artistVideoAlbums(string $username)
    {
        return $this->service->artistVideoAlbums($username);
    }
}
