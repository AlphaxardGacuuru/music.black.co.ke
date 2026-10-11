<?php

namespace Database\Seeders;

use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create one story for @blackmusic
        $blackMusicId = User::where('username', '@blackmusic')->value('id');

        if ($blackMusicId) {
            Story::factory()->create(['user_id' => $blackMusicId]);
        }

        // Ten stories by random existing users
        for ($i = 0; $i < 10; $i++) {
            Story::factory()->create(['user_id' => User::inRandomOrder()->value('id')]);
        }
    }
}
