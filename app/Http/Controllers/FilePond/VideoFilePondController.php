<?php

namespace App\Http\Controllers\FilePond;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilePond\Concerns\DeletesUnclaimedUploads;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class VideoFilePondController extends Controller
{
    use DeletesUnclaimedUploads;

    /*
     * Handle Video Thumbnail Upload
     */
    public function storeVideoThumbnail(Request $request): string
    {
        $request->validate([
            'filepond-thumbnail' => 'required|image',
        ]);

        return $request->file('filepond-thumbnail')->store('video-thumbnails', 'public');
    }

    /*
     * Handle Video Upload
     */
    public function storeVideo(Request $request): string
    {
        $request->validate([
            'filepond-video' => 'required|file',
        ]);

        return $request->file('filepond-video')->store('videos', 'public');
    }

    /*
     * Update Video Thumbnail
     */
    public function updateVideoThumbnail(Request $request, string $id): void
    {
        $request->validate([
            'filepond-thumbnail' => 'required|image',
        ]);

        $thumbnail = $request->file('filepond-thumbnail')->store('video-thumbnails', 'public');

        $video = Video::findOrFail($id);

        Gate::authorize('update', $video);

        // Delete thumbnail
        Storage::disk('public')->delete(array_filter([$video->getRawOriginal('thumbnail')]));

        // Update Thumbnail
        $video->thumbnail = $thumbnail;
        $video->save();
    }

    /*
     * Update Video
     */
    public function updateVideo(Request $request, string $id): void
    {
        $request->validate([
            'filepond-video' => 'required|file',
        ]);

        $videoFile = $request->file('filepond-video')->store('videos', 'public');

        $video = Video::findOrFail($id);

        Gate::authorize('update', $video);

        // Delete video
        Storage::disk('public')->delete(array_filter([$video->getRawOriginal('video')]));

        // Update Video
        $video->video = $videoFile;
        $video->save();
    }

    /*
     * Handle Video Thumbnail Delete
     */
    public function destoryVideoThumbnail(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'video-thumbnails',
            $id,
            fn(string $path) => Video::where('thumbnail', $path)->exists(),
            'Video thumbnail deleted'
        );
    }

    /*
     * Handle Video Delete
     */
    public function destoryVideo(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'videos',
            $id,
            fn(string $path) => Video::where('video', $path)->exists(),
            'Video deleted'
        );
    }
}
