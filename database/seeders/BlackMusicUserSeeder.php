<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class BlackMusicUserSeeder extends Seeder
{
    /**
     * The house @blackmusic account. It runs before every other user seeder
     * because new users automatically follow it.
     */
    public function run(): void
    {
        if (User::query()->where('username', '@blackmusic')->doesntExist()) {
            User::factory()->black()->create();
        }
    }
}
