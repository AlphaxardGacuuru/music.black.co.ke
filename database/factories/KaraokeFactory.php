<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\Karaoke;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Karaoke>
 */
class KaraokeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'karaoke' => 'karaokes/'.rand(1, 4).'.mp4',
            'user_id' => User::factory(),
            'audio_id' => fn () => Audio::factory(),
            'description' => fake()->realText(20, 2),
        ];
    }

    /**
     * Use random existing users and audios instead of creating new ones
     * (what the OLD factory always did, used by the seeder).
     */
    public function fromExisting(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::inRandomOrder()->value('id'),
            'audio_id' => Audio::inRandomOrder()->value('id'),
        ]);
    }
}
