<?php

namespace App\Http\Services;

use App\Http\Resources\SavedKaraokeResource;
use App\Models\SavedKaraoke;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavedKaraokeService extends Service
{
    /**
     * Display the current user's saved karaokes (oldest first).
     */
    public function index(): AnonymousResourceCollection
    {
        $getSavedKarokes = SavedKaraoke::where('user_id', $this->id)
            ->orderBy('created_at', 'ASC')
            ->get();

        return SavedKaraokeResource::collection($getSavedKarokes);
    }

    /**
     * Toggle the current user's save on a karaoke.
     */
    public function store(Request $request): array
    {
        $savedKaraoke = 'data';

        $query = SavedKaraoke::where('karaoke_id', $request->input('id'))
            ->where('user_id', $this->id);

        if ($query->exists()) {
            $query->delete();

            $message = 'Karaoke removed';
            $added = false;
        } else {
            $savedKaraoke = new SavedKaraoke;
            $savedKaraoke->karaoke_id = $request->input('id');
            $savedKaraoke->user_id = $this->id;
            $savedKaraoke->save();

            $message = 'Karaoke saved';
            $added = true;
        }

        return [$added, $message, $savedKaraoke];
    }
}
