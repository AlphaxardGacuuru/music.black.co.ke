<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'text' => fake()->realText(20, 2),
            'media' => 'post-media/'.rand(1, 5).'.jpg',
            'parameter_1' => '',
            'parameter_2' => '',
            'parameter_3' => '',
            'parameter_4' => '',
            'parameter_5' => '',
        ];
    }
}
