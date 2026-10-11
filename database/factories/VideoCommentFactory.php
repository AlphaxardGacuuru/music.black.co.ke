<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoComment>
 */
class VideoCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'text' => fake()->realText(20, 2),
            'user_id' => User::factory(),
        ];
    }
}
