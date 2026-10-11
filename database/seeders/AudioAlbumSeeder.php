<?php

namespace Database\Seeders;

use App\Models\AudioAlbum;
use App\Models\User;
use Illuminate\Database\Seeder;

class AudioAlbumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Needs users (including @blackmusic) to exist already.
     */
    public function run(): void
    {
        $users = User::all();

        $blackMusic = $users->firstWhere('username', '@blackmusic');

        if ($blackMusic) {
            AudioAlbum::factory()->create(['user_id' => $blackMusic->id]);
        }

        AudioAlbum::factory()
            ->count(10)
            ->state(fn () => ['user_id' => $users->random()->id])
            ->create();
    }
}
