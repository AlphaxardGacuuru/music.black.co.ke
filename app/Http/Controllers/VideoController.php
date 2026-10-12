<?php

namespace App\Http\Controllers;

use App\Events\VideoUploadedEvent;
use App\Http\Services\VideoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function __construct(protected VideoService $service)
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
        // Handle form for video
        $request->validate([
            'video' => 'required|string',
            'name' => 'required|string',
            // Without one, ProcessVideoJob uses a frame from the video.
            'thumbnail' => 'nullable|string',
            'ft' => 'nullable|exists:users,username',
        ]);

        [$saved, $message, $video] = $this->service->store($request);

        VideoUploadedEvent::dispatchIf($saved, $video);

        return response([
            'message' => $message,
            'data' => $video,
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
            'name' => 'nullable|string',
            'ft' => 'nullable|exists:users,username',
        ]);

        [$saved, $message, $video] = $this->service->update($request, $id);

        return response([
            'message' => $message,
            'data' => $video,
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
     * Download Video
     */
    public function download(Request $request, ?string $id = null): BinaryFileResponse
    {
        $id ??= $request->query('id');

        [$src, $name] = $this->service->download($id);

        return response()->download($src, $name);
    }

    /*
     * Display a listing of the charts.
     */
    public function newlyReleased()
    {
        return $this->service->newlyReleased();
    }

    public function trending()
    {
        return $this->service->trending();
    }

    public function topDownloaded()
    {
        return $this->service->topDownloaded();
    }

    public function topLiked()
    {
        return $this->service->topLiked();
    }

    /*
     * Artist's Videos
     */
    public function artistVideos(string $username)
    {
        return $this->service->artistVideos($username);
    }
}
