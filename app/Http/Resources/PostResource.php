<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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

        // Get votes of each parameter as a percentage
        $percentage1 = $this->percentage((string) $this->parameter_1);
        $percentage2 = $this->percentage((string) $this->parameter_2);
        $percentage3 = $this->percentage((string) $this->parameter_3);
        $percentage4 = $this->percentage((string) $this->parameter_4);
        $percentage5 = $this->percentage((string) $this->parameter_5);

        $pollsPercentages = [
            (string) $this->parameter_1 => $percentage1,
            (string) $this->parameter_2 => $percentage2,
            (string) $this->parameter_3 => $percentage3,
            (string) $this->parameter_4 => $percentage4,
            (string) $this->parameter_5 => $percentage5,
        ];

        // Get parameter with the most votes
        $winner = array_keys($pollsPercentages, max($pollsPercentages));

        $winner = count($winner) > 1 ? '' : $winner[0];

        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'username' => $this->user->username,
            'avatar' => $this->user->avatar,
            'decos' => $this->user->decos,
            'text' => $this->text,
            'media' => $this->media,
            'parameter1' => $this->parameter_1,
            'parameter2' => $this->parameter_2,
            'parameter3' => $this->parameter_3,
            'parameter4' => $this->parameter_4,
            'parameter5' => $this->parameter_5,
            'hasVoted1' => $this->hasVoted($userId, (string) $this->parameter_1),
            'hasVoted2' => $this->hasVoted($userId, (string) $this->parameter_2),
            'hasVoted3' => $this->hasVoted($userId, (string) $this->parameter_3),
            'hasVoted4' => $this->hasVoted($userId, (string) $this->parameter_4),
            'hasVoted5' => $this->hasVoted($userId, (string) $this->parameter_5),
            'percentage1' => $percentage1,
            'percentage2' => $percentage2,
            'percentage3' => $percentage3,
            'percentage4' => $percentage4,
            'percentage5' => $percentage5,
            'winner' => $winner,
            'totalVotes' => $this->polls->count(),
            'isWithin24Hrs' => $this->isWithin24Hrs(),
            'hasMuted' => filter_var($this->hasMuted($userId), FILTER_VALIDATE_BOOLEAN),
            'hasFollowed' => $this->hasFollowed($userId),
            'hasLiked' => $this->hasLiked($userId),
            'hasEdited' => filter_var($this->has_edited, FILTER_VALIDATE_BOOLEAN),
            'likes' => $this->likes->count(),
            'comments' => $this->comments->count(),
            'updatedAt' => $this->updated_at,
            'createdAt' => $this->created_at,
        ];
    }
}
