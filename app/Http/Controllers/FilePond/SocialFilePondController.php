<?php

namespace App\Http\Controllers\FilePond;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilePond\Concerns\DeletesUnclaimedUploads;
use App\Models\Chat;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SocialFilePondController extends Controller
{
    use DeletesUnclaimedUploads;

    /*
     * Posts
     */

    /**
     * Handle Post Media upload.
     */
    public function storePostMedia(Request $request): string
    {
        $request->validate([
            'filepond-media' => 'required',
        ]);

        return $request->file('filepond-media')->store('post-media', 'public');
    }

    /**
     * Handle Post Media delete.
     */
    public function destroyPostMedia(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'post-media',
            $id,
            fn(string $path) => Post::where('media', $path)->exists()
                || PostComment::where('media', $path)->exists(),
            'Post media deleted'
        );
    }

    /*
     * Chats
     */

    /**
     * Handle Chat Media upload.
     */
    public function storeChatMedia(Request $request): string
    {
        $request->validate([
            'filepond-media' => 'required',
        ]);

        return $request->file('filepond-media')->store('chat-media', 'public');
    }

    /**
     * Handle Chat Media delete.
     */
    public function deleteChatMedia(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'chat-media',
            $id,
            fn(string $path) => Chat::withTrashed()->where('media', $path)->exists(),
            'Chat media deleted'
        );
    }

    /*
     * Stories
     */

    /**
     * Handle Story upload. Returns e.g. {"image": "stories/xyz.jpg"} or
     * {"video": "stories/xyz.mp4"} (the first five characters of the mime type).
     */
    public function storeStory(Request $request): array
    {
        $request->validate([
            'filepond-media' => 'required',
        ]);

        $file = $request->file('filepond-media');
        // Get Mime Type
        $mime = substr($file->getMimeType(), 0, 5);
        // Store File
        $media = $file->store('stories', 'public');

        return [$mime => $media];
    }

    /**
     * Handle Story delete.
     */
    public function deleteStory(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'stories',
            $id,
            fn(string $path) => Story::where('media', 'like', '%' . basename($path) . '%')->exists(),
            'Story deleted'
        );
    }
}
