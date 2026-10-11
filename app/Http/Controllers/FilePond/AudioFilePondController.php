<?php

namespace App\Http\Controllers\FilePond;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilePond\Concerns\DeletesUnclaimedUploads;
use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AudioFilePondController extends Controller
{
    use DeletesUnclaimedUploads;

    /*
     * Handle Audio Thumbnail Upload */
    public function storeAudioThumbnail(Request $request): string
    {
        $request->validate([
            'filepond-thumbnail' => 'required|image',
        ]);

        return $request->file('filepond-thumbnail')->store('audio-thumbnails', 'public');
    }

    /*
     * Handle Audio Upload */
    public function storeAudio(Request $request): string
    {
        $request->validate([
            'filepond-audio' => 'required|file',
        ]);

        return $request->file('filepond-audio')->store('audios', 'public');
    }

    /*
     * Update Audio Thumbnail */
    public function updateAudioThumbnail(Request $request, string $id): void
    {
        $request->validate([
            'filepond-thumbnail' => 'required|image',
        ]);

        $audio = Audio::findOrFail($id);

        Gate::authorize('update', $audio);

        $thumbnail = $request->file('filepond-thumbnail')->store('audio-thumbnails', 'public');

        // Delete old thumbnail
        Storage::disk('public')->delete($audio->getRawOriginal('thumbnail'));

        // Update Thumbnail
        $audio->thumbnail = $thumbnail;
        $audio->save();
    }

    /*
     * Update Audio */
    public function updateAudio(Request $request, string $id): void
    {
        $request->validate([
            'filepond-audio' => 'required|file',
        ]);

        $audio = Audio::findOrFail($id);

        Gate::authorize('update', $audio);

        $audioFile = $request->file('filepond-audio')->store('audios', 'public');

        // Delete old audio
        Storage::disk('public')->delete($audio->getRawOriginal('audio'));

        // Update Audio
        $audio->audio = $audioFile;
        $audio->save();
    }

    /*
     * Handle Audio Thumbnail Delete */
    public function destoryAudioThumbnail(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'audio-thumbnails',
            $id,
            fn(string $path) => Audio::where('thumbnail', $path)->exists(),
            'Audio thumbnail deleted'
        );
    }

    /*
     * Handle Audio Delete */
    public function destoryAudio(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'audios',
            $id,
            fn(string $path) => Audio::where('audio', $path)->exists(),
            'Audio deleted'
        );
    }
}
