<?php

namespace Database\Factories;

use App\Models\AudioAlbum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioAlbum>
 */
class AudioAlbumFactory extends Factory
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
            'cover' => 'audio-album-covers/' . rand(1, 5) . '.jpg',
            'released' => fake()->dateTime(),
            'user_id' => User::factory(),
        ];
    }
}
