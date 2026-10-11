<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedKaraokeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth('sanctum')->id();

        $karaoke = $this->karaoke;

        return [
            'id' => $karaoke->id,
            'karaoke' => $karaoke->karaoke,
            'audioId' => $karaoke->audio_id,
            'audioName' => $karaoke->audio->name,
            'audioThumbnail' => $karaoke->audio->thumbnail,
            'name' => $karaoke->user->name,
            'username' => $karaoke->user->username,
            'avatar' => $karaoke->user->avatar,
            'decos' => $karaoke->user->decos,
            'description' => $karaoke->description,
            'hasLiked' => $karaoke->hasLiked($userId),
            'hasSaved' => $karaoke->hasSaved($userId),
            'likes' => $karaoke->likes->count(),
            'comments' => $karaoke->comments->count(),
            'createdAt' => $karaoke->created_at,
        ];
    }
}
