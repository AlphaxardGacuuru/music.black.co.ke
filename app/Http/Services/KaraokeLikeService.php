<?php

namespace App\Http\Services;

use App\Models\KaraokeLike;
use Illuminate\Http\Request;

class KaraokeLikeService extends Service
{
    /**
     * Toggle the current user's like on a karaoke.
     */
    public function store(Request $request): array
    {
        $karaokeLike = 'data';

        $query = KaraokeLike::where('karaoke_id', $request->input('karaoke'))
            ->where('user_id', $this->id);

        if ($query->exists()) {
            $query->delete();

            $message = 'Like removed';
            $added = false;
        } else {
            $karaokeLike = new KaraokeLike;
            $karaokeLike->karaoke_id = $request->input('karaoke');
            $karaokeLike->user_id = $this->id;
            $karaokeLike->save();

            $message = 'Karaoke liked';
            $added = true;
        }

        return [$added, $message, $karaokeLike];
    }
}
