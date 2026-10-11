<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'followed_id' => User::factory(),
            'user_id' => User::factory(),
            'muted' => ['posts' => false, 'stories' => false],
            'blocked' => null,
        ];
    }
}
