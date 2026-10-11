<?php

namespace Tests\Feature\Music\Audio;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\CartAudio;
use App\Models\User;
use Database\Seeders\AudioAlbumSeeder;
use Database\Seeders\AudioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_seeders_run(): void
    {
        User::factory()->create(['username' => '@blackmusic']);
        User::factory()->count(2)->create();

        $this->seed([AudioAlbumSeeder::class, AudioSeeder::class]);

        $this->assertSame(11, AudioAlbum::count());
        $this->assertSame(10, Audio::count());
        $this->assertSame(30, CartAudio::count());
    }
}
