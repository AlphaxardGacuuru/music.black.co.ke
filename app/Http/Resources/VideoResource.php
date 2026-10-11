<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
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
            'video' => $this->video,
            'name' => $this->name,
            'artistName' => $this->user->name,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar,
            'artistDecos' => $this->user->decos,
            'ft' => $this->ft?->username,
            'videoAlbumId' => $this->video_album_id,
            'album' => $this->album->name,
            'genre' => $this->genre,
            'thumbnail' => $this->thumbnail,
            'description' => $this->description,
            'released' => $this->released,
            'hasLiked' => $this->hasLiked($userId),
            'likes' => $this->likes->count(),
            'comments' => $this->comments->count(),
            'inCart' => $this->inCart($userId),
            'hasBoughtVideo' => $this->hasBoughtVideo($userId),
            'hasBought1' => $this->user->hasBought1($userId),
            'hasFollowed' => $this->user->hasFollowed($userId),
            'downloads' => $this->bought->count(),
            'createdAt' => $this->created_at,
        ];
    }
}
