<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaraokeCommentResource extends JsonResource
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
            'karaoke_id' => $this->karaoke_id,
            'text' => $this->text,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'hasLiked' => $this->hasLiked($userId),
            'likes' => $this->likes->count(),
            'created_at' => $this->created_at,
        ];
    }
}
