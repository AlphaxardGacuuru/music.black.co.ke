<?php

namespace Database\Factories;

use App\Models\Deco;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deco>
 */
class DecoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // The decorated fan
            'user_id' => User::factory(),
            'artist_id' => User::factory()->state(['account_type' => 'musician']),
        ];
    }
}
