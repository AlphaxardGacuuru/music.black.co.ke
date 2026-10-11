<?php

namespace Database\Factories;

use App\Models\SeenStory;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeenStory>
 */
class SeenStoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'story_id' => Story::factory(),
            'user_id' => User::factory(),
            'seen_at' => now(),
        ];
    }
}
