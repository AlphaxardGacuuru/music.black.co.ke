<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Current user's id, null for guests
        $userId = auth('sanctum')->id();

        return [
            'id' => $this->id,
            'audioId' => $this->audio_id,
            'text' => $this->text,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'decos' => $this->user->decos,
            'hasLiked' => $this->hasLiked($userId),
            'likes' => $this->likes->count(),
            'createdAt' => $this->created_at,
        ];
    }
}
