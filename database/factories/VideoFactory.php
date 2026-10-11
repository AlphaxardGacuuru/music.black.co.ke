<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\VideoAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
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
            'video' => 'videos/' . rand(1, 5) . '.mp4',
            'name' => fake()->catchPhrase(),
            'video_album_id' => VideoAlbum::factory(),
            // The video belongs to the album's artist
            'user_id' => fn (array $attributes) => VideoAlbum::find($attributes['video_album_id'])->user_id,
            'genre' => $genres[rand(0, 4)],
            'thumbnail' => 'video-thumbnails/' . rand(1, 4) . '.jpg',
            'description' => fake()->realText(20, 2),
            'released' => fake()->dateTime(),
        ];
    }
}
