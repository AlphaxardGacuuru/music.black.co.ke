<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a BoughtAudio (or, for purchase receipts, the CartAudio it was bought
 * from). Both have a audio() relation.
 */
class BoughtAudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->audio->id,
            'audio' => $this->audio->audio,
            'name' => $this->audio->name,
            'artistName' => $this->audio->user->name,
            'username' => $this->audio->user->username,
            'avatar' => $this->audio->user->avatar,
            'artistDecos' => $this->audio->user->decos,
            'ft' => $this->audio->ft?->username,
            'audioAlbumId' => $this->audio->audio_album_id,
            'album' => $this->audio->album?->name,
            'genre' => $this->audio->genre,
            'thumbnail' => $this->audio->thumbnail,
            'description' => $this->audio->description,
            'released' => $this->audio->released,
            'hasBoughtAudio' => true,
            'createdAt' => $this->audio->created_at,
        ];
    }
}
