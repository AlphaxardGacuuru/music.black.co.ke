<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config("app.env") == "production") {
            $this->call([
                RoleSeeder::class,
                BlackMusicUserSeeder::class,
                AdminUserSeeder::class,
            ]);
        } else {
            $this->call([
                RoleSeeder::class,
                BlackMusicUserSeeder::class,
                AdminUserSeeder::class,
                UserSeeder::class,
                ReferralSeeder::class,
                VideoAlbumSeeder::class,
                VideoSeeder::class,
                AudioAlbumSeeder::class,
                AudioSeeder::class,
                BoughtVideoSeeder::class,
                BoughtAudioSeeder::class,
                KaraokeSeeder::class,
                PostSeeder::class,
                PollSeeder::class,
                StorySeeder::class,
                ChatSeeder::class,
            ]);
        }
    }
}
