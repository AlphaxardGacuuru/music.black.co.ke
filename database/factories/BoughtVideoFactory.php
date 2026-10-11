<?php

namespace Database\Factories;

use App\Models\BoughtVideo;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoughtVideo>
 */
class BoughtVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => fn () => Video::factory(),
            'price' => (string) BoughtVideo::PRICE,
            // The buyer
            'user_id' => User::factory(),
            // The video's owner
            'artist_id' => fn (array $attributes) => Video::find($attributes['video_id'])->user_id,
        ];
    }
}
