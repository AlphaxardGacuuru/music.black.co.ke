<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaraokeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth('sanctum')->id();

        return [
            'id' => $this->id,
            'karaoke' => $this->karaoke,
            'audioId' => $this->audio_id,
            'audioName' => $this->audio->name,
            'audioThumbnail' => $this->audio->thumbnail,
            'name' => $this->user->name,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar,
            'decos' => $this->user->decos,
            'description' => $this->description,
            'hasLiked' => $this->hasLiked($userId),
            'hasSaved' => $this->hasSaved($userId),
            'likes' => $this->likes->count(),
            'comments' => $this->comments->count(),
            'created_at' => $this->created_at,
        ];
    }
}
