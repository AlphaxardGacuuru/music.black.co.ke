<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaraokeAudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audioId' => $this->audio_id,
            'username' => $this->user->username,
            'name' => $this->audio->name,
            'thumbnail' => $this->audio->thumbnail,
            'createdAt' => $this->created_at,
        ];
    }
}
