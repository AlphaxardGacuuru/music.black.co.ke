<?php

namespace Database\Factories;

use App\Models\KaraokeLike;
use App\Models\Karaoke;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaraokeLike>
 */
class KaraokeLikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'karaoke_id' => Karaoke::factory(),
            'user_id' => User::factory(),
        ];
    }
}
