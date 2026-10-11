<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create one post for @blackmusic
        $blackMusicId = User::where('username', '@blackmusic')->value('id');

        if ($blackMusicId) {
            Post::factory()->create(['user_id' => $blackMusicId]);
        }

        // Ten posts by random existing users
        for ($i = 0; $i < 10; $i++) {
            Post::factory()->create(['user_id' => User::inRandomOrder()->value('id')]);
        }
    }
}
