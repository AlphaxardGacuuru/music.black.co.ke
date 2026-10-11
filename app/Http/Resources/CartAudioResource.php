<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartAudioResource extends JsonResource
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
            'cartId' => $this->id,
            'id' => $this->audio_id,
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
            'hasLiked' => $this->audio->hasLiked($userId),
            'likes' => $this->audio->likes->count(),
            'comments' => $this->audio->comments->count(),
            'inCart' => $this->audio->inCart($userId),
            'hasBoughtAudio' => $this->audio->hasBoughtAudio($userId),
            'hasBought1' => $this->audio->user->hasBought1($userId),
            'hasFollowed' => $this->audio->user->hasFollowed($userId),
            'downloads' => $this->audio->bought->count(),
            'createdAt' => $this->audio->created_at,
        ];
    }
}
