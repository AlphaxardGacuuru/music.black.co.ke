<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VideoAlbum;
use Illuminate\Database\Seeder;

class VideoAlbumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blackMusic = User::where('username', '@blackmusic')->first();

        if ($blackMusic) {
            VideoAlbum::factory()->create(['user_id' => $blackMusic->id]);
        }

        VideoAlbum::factory()
            ->count(10)
            ->state(fn () => ['user_id' => User::inRandomOrder()->value('id')])
            ->create();
    }
}
