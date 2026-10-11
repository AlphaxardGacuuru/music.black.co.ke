<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartVideoResource extends JsonResource
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
            'id' => $this->video_id,
            'video' => $this->video->video,
            'name' => $this->video->name,
            'artistName' => $this->video->user->name,
            'username' => $this->video->user->username,
            'avatar' => $this->video->user->avatar,
            'artistDecos' => $this->video->user->decos,
            'ft' => $this->video->ft?->username,
            'videoAlbumId' => $this->video->video_album_id,
            'album' => $this->video->album?->name,
            'genre' => $this->video->genre,
            'thumbnail' => $this->video->thumbnail,
            'description' => $this->video->description,
            'released' => $this->video->released,
            'hasLiked' => $this->video->hasLiked($userId),
            'likes' => $this->video->likes->count(),
            'comments' => $this->video->comments->count(),
            'inCart' => $this->video->inCart($userId),
            'hasBoughtVideo' => $this->video->hasBoughtVideo($userId),
            'hasBought1' => $this->video->user->hasBought1($userId),
            'hasFollowed' => $this->video->user->hasFollowed($userId),
            'downloads' => $this->video->bought->count(),
            'createdAt' => $this->video->created_at,
        ];
    }
}
