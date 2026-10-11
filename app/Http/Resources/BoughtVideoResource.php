<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a BoughtVideo (or, for purchase receipts, the CartVideo it was bought
 * from). Both have a video() relation.
 */
class BoughtVideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->video->id,
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
            'hasBoughtVideo' => true,
            'createdAt' => $this->video->created_at,
        ];
    }
}
