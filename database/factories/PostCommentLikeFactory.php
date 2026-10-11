<?php

namespace Database\Factories;

use App\Models\PostComment;
use App\Models\PostCommentLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostCommentLike>
 */
class PostCommentLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_comment_id' => PostComment::factory(),
            'user_id' => User::factory(),
        ];
    }
}
