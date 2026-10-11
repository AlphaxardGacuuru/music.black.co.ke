<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\AudioAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audio>
 */
class AudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $genres = ['Afro', 'Benga', 'Blues', 'Boomba', 'Country'];

        return [
            'audio' => 'audios/' . rand(1, 4) . '.mp3',
            'name' => fake()->catchPhrase(),
            'audio_album_id' => AudioAlbum::factory(),
            // The audio belongs to the album's artist
            'user_id' => fn (array $attributes) => AudioAlbum::find($attributes['audio_album_id'])->user_id,
            'genre' => $genres[rand(0, 4)],
            'thumbnail' => 'audio-thumbnails/' . rand(1, 5) . '.jpg',
            'description' => fake()->realText(20, 2),
            'released' => fake()->dateTime(),
        ];
    }
}
