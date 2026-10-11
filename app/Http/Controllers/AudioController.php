<?php

namespace App\Http\Controllers;

use App\Http\Resources\AudioResource;
use App\Http\Services\AudioService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AudioController extends Controller
{
    public function __construct(protected AudioService $service)
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
            'audio' => 'required|string',
            'name' => 'required|string',
            'thumbnail' => 'required',
            'ft' => 'nullable|exists:users,username',
            'audio_album_id' => 'required|exists:audio_albums,id',
            'genre' => 'required|string',
        ]);

        [$saved, $message, $audio] = $this->service->store($request);

        return response([
            'message' => $message,
            'data' => $audio,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): AudioResource
    {
        return $this->service->show($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): Response
    {
        $request->validate([
            'name' => 'nullable|string',
            'ft' => 'nullable|exists:users,username',
            'audio_album_id' => 'nullable|exists:audio_albums,id',
        ]);

        [$saved, $message, $audio] = $this->service->update($request, $id);

        return response([
            'message' => $message,
            'data' => $audio,
        ], 200);
    }

    /*
     * Download Audio
     *
     * The OLD frontend calls "audios/download/{id}"; "audios/download?id="
     * is accepted too.
     */
    public function download(Request $request, ?string $id = null): BinaryFileResponse
    {
        $id ??= $request->query('id');

        abort_unless(is_string($id) && $id !== '', 404);

        [$src, $name] = $this->service->download($id);

        return response()->download($src, $name);
    }

    /*
     * Display a listing of the charts.
     */
    public function newlyReleased(): Response
    {
        return $this->service->newlyReleased();
    }

    public function trending(): Response
    {
        return $this->service->trending();
    }

    public function topDownloaded(): Response
    {
        return $this->service->topDownloaded();
    }

    public function topLiked(): Response
    {
        return $this->service->topLiked();
    }

    /*
     * Artist's Audios */
    public function artistAudios(string $username): AnonymousResourceCollection
    {
        return $this->service->artistAudios($username);
    }
}
