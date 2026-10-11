<?php

namespace Database\Seeders;

use App\Models\Karaoke;
use Illuminate\Database\Seeder;

class KaraokeSeeder extends Seeder
{
    /**
     * Run the database seeds. Needs users and audios to exist already
     * (run after UserSeeder, AudioAlbumSeeder and AudioSeeder).
     */
    public function run(): void
    {
        Karaoke::factory()->count(10)->fromExisting()->create();
    }
}
