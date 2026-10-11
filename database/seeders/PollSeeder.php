<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Post
        $post = Post::factory()->create([
            'media' => null,
            'parameter_1' => 'A',
            'parameter_2' => 'B',
            'parameter_3' => 'C',
            'parameter_4' => 'D',
            'parameter_5' => 'E',
            'user_id' => User::inRandomOrder()->value('id'),
            'created_at' => Carbon::now()->subHours(24),
        ]);

        // Add a vote for each user, alternating between the first two options
        foreach (User::all()->values() as $index => $user) {
            Poll::factory()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parameter' => $index % 2 === 0 ? $post->parameter_1 : $post->parameter_2,
            ]);
        }
    }
}
