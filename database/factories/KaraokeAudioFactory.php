<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\KaraokeAudio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaraokeAudio>
 */
class KaraokeAudioFactory extends Factory
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
