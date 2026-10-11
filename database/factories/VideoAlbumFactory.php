<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VideoAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoAlbum>
 */
class VideoAlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'cover' => 'video-album-covers/' . rand(1, 5) . '.jpg',
            'released' => fake()->dateTime(),
            'user_id' => User::factory(),
        ];
    }
}
