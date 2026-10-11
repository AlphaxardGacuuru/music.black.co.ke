<?php

namespace App\Http\Controllers\FilePond;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FilePond\Concerns\DeletesUnclaimedUploads;
use App\Models\Karaoke;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KaraokeFilePondController extends Controller
{
    use DeletesUnclaimedUploads;

    /**
     * Handle Karaoke upload.
     */
    public function storeKaraoke(Request $request): string
    {
        $this->validate($request, [
            'filepond-karaoke' => 'required',
        ]);

        return $request->file('filepond-karaoke')->store('karaokes', 'public');
    }

    /**
     * Handle Karaoke delete.
     */
    public function destroyKaraoke(string $id): Response
    {
        return $this->deleteUnclaimedUpload(
            'karaokes',
            $id,
            fn(string $path) => Karaoke::where('karaoke', $path)->exists(),
            'Karaoke deleted'
        );
    }
}
