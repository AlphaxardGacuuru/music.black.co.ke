<?php

namespace Database\Factories;

use App\Models\Karaoke;
use App\Models\KaraokeComment;
use App\Models\KaraokeCommentLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaraokeComment>
 */
class KaraokeCommentFactory extends Factory
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
            'text' => fake()->realText(20, 2),
        ];
    }

    /**
     * Add a like from a random existing user after creation (what the OLD
     * factory always did in configure()).
     */
    public function withLike(): static
    {
        return $this->afterCreating(function (KaraokeComment $comment) {
            KaraokeCommentLike::factory()->create([
                'user_id' => User::inRandomOrder()->value('id'),
                'karaoke_comment_id' => $comment->id,
            ]);
        });
    }
}
