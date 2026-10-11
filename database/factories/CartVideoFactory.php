<?php

namespace Database\Factories;

use App\Models\CartVideo;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartVideo>
 */
class CartVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => fn () => Video::factory(),
            'user_id' => User::factory(),
        ];
    }
}
