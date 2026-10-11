<?php

namespace Database\Factories;

use App\Models\AudioComment;
use App\Models\AudioCommentLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioCommentLike>
 */
class AudioCommentLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audio_comment_id' => AudioComment::factory(),
            'user_id' => User::factory(),
        ];
    }
}
