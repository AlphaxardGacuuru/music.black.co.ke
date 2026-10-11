<?php

namespace Database\Seeders;

use App\Models\BoughtVideo;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class BoughtVideoSeeder extends Seeder
{
    /**
     * Seed 10 purchases of random videos by random users. Needs users and
     * videos to be seeded first.
     */
    public function run(): void
    {
        $videos = Video::all();
        $users = User::all();

        if ($videos->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach (range(1, 10) as $i) {
            $video = $videos->random();

            // Each user can only buy a video once
            BoughtVideo::firstOrCreate(
                [
                    'video_id' => $video->id,
                    'user_id' => $users->random()->id,
                ],
                [
                    'price' => (string) BoughtVideo::PRICE,
                    'artist_id' => $video->user_id,
                ],
            );
        }
    }
}
