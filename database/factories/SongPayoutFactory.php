<?php

namespace Database\Factories;

use App\Models\SongPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongPayout>
 */
class SongPayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['account_type' => 'musician']),
            'amount' => (string) fake()->numberBetween(100, 5000),
        ];
    }
}
