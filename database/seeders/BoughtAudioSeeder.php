<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\BoughtAudio;
use App\Models\User;
use Illuminate\Database\Seeder;

class BoughtAudioSeeder extends Seeder
{
    /**
     * Seed 10 purchases of random audios by random users. Needs users and
     * audios to be seeded first.
     */
    public function run(): void
    {
        $audios = Audio::all();
        $users = User::all();

        if ($audios->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach (range(1, 10) as $i) {
            $audio = $audios->random();

            // Each user can only buy a audio once
            BoughtAudio::firstOrCreate(
                [
                    'audio_id' => $audio->id,
                    'user_id' => $users->random()->id,
                ],
                [
                    'price' => (string) BoughtAudio::PRICE,
                    'artist_id' => $audio->user_id,
                ],
            );
        }
    }
}
