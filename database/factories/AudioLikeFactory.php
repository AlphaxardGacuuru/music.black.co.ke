<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\AudioLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioLike>
 */
class AudioLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audio_id' => Audio::factory(),
            'user_id' => User::factory(),
        ];
    }
}
