<?php

namespace Database\Factories;

use App\Models\KaraokeCommentLike;
use App\Models\KaraokeComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaraokeCommentLike>
 */
class KaraokeCommentLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'karaoke_comment_id' => KaraokeComment::factory(),
            'user_id' => User::factory(),
        ];
    }
}
