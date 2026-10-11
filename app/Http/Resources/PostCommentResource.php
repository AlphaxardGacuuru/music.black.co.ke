<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Current user's id, null for guests
        $userId = auth('sanctum')->user()?->id;

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'name' => $this->user->name,
            'username' => $this->user->username,
            'decos' => $this->user->decos,
            'avatar' => $this->user->avatar,
            'text' => $this->text,
            'likes' => $this->likes->count(),
            'hasLiked' => $this->hasLiked($userId),
            'created_at' => $this->created_at,
        ];
    }
}
