<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VideoComment;
use App\Models\VideoCommentLike;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoCommentLike>
 */
class VideoCommentLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_comment_id' => VideoComment::factory(),
            'user_id' => User::factory(),
        ];
    }
}
