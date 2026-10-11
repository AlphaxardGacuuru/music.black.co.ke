<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
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
            'name' => $this->user->name,
            'username' => $this->user->username,
            'to' => $this->to?->username,
            'avatar' => $this->user->avatar,
            'decos' => $this->user->decos,
            'text' => $this->text,
            'media' => $this->media,
            'createdAt' => $this->created_at,
        ];
    }
}
