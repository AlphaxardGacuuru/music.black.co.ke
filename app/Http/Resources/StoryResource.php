<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
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
            'name' => $this->user->name,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar,
            'media' => $this->media,
            'text' => $this->text,
            // "muted" is selected from the follows join in StoryService::index
            'hasMuted' => filter_var($this->muted, FILTER_VALIDATE_BOOLEAN),
            'hasSeen' => $this->hasSeen($userId),
        ];
    }
}
