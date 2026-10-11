<?php

namespace App\Http\Controllers\FilePond\Concerns;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * FilePond's "revert" removes a file the user just uploaded but hasn't saved
 * yet. Stored paths are public (they appear in API responses), so a revert
 * may only delete a file nothing references; once a song, post, chat or
 * story uses it, it can only go through that record's own delete.
 */
trait DeletesUnclaimedUploads
{
    /**
     * @param  Closure(string): bool  $isClaimed  Given the stored path, whether a record uses it.
     */
    protected function deleteUnclaimedUpload(
        string $directory,
        string $id,
        Closure $isClaimed,
        string $message
    ): Response {
        $path = $directory . '/' . basename($id);

        if ($isClaimed($path)) {
            return response('This file is in use and cannot be deleted', 403);
        }

        Storage::disk('public')->delete($path);

        return response($message, 200);
    }
}
