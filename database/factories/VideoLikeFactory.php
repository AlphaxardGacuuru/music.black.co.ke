<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoLike;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoLike>
 */
class VideoLikeFactory extends Factory
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
            'user_id' => User::factory(),
        ];
    }
}
