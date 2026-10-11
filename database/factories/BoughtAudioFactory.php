<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoughtAudio>
 */
class BoughtAudioFactory extends Factory
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
            'price' => (string) BoughtAudio::PRICE,
            // The buyer
            'user_id' => User::factory(),
            // The audio's owner
            'artist_id' => fn (array $attributes) => Audio::find($attributes['audio_id'])->user_id,
        ];
    }
}
