<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\AudioComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioComment>
 */
class AudioCommentFactory extends Factory
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
            'text' => fake()->realText(20, 2),
            'user_id' => User::factory(),
        ];
    }
}
