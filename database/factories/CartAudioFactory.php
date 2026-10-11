<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\CartAudio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartAudio>
 */
class CartAudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audio_id' => fn () => Audio::factory(),
            'user_id' => User::factory(),
        ];
    }
}
