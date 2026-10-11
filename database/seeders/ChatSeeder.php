<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blackMusicId = User::where('username', '@blackmusic')->value('id');

        if (! $blackMusicId) {
            return;
        }

        // Five chats from @blackmusic to random existing users
        for ($i = 0; $i < 5; $i++) {
            Chat::factory()->create([
                'user_id' => $blackMusicId,
                'to_id' => User::inRandomOrder()->value('id'),
            ]);
        }
    }
}
