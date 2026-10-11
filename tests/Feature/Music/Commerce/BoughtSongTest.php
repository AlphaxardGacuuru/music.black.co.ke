<?php

namespace Tests\Feature\Music\Commerce;

use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\User;
use Database\Seeders\BoughtAudioSeeder;
use Database\Seeders\BoughtVideoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoughtSongTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    public function test_bought_audios_lists_the_users_purchases(): void
    {
        $user = User::factory()->create();
        $artist = $this->musician();
        $audio = $this->makeAudio($artist);
        BoughtAudio::factory()->create(['audio_id' => $audio->id, 'user_id' => $user->id]);
        BoughtAudio::factory()->create(['audio_id' => $this->makeAudio()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/bought-audios')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $audio->id)
            ->assertJsonPath('data.0.username', $artist->username)
            ->assertJsonPath('data.0.hasBoughtAudio', true)
            ->assertJsonStructure(['data' => [[
                'id', 'audio', 'name', 'artistName', 'username', 'avatar', 'artistDecos',
                'ft', 'audioAlbumId', 'album', 'genre', 'thumbnail', 'description',
                'released', 'hasBoughtAudio', 'createdAt',
            ]]]);
    }

    public function test_guests_have_no_bought_songs(): void
    {
        BoughtAudio::factory()->create(['audio_id' => $this->makeAudio()->id]);
        BoughtVideo::factory()->create(['video_id' => $this->makeVideo()->id]);

        $this->getJson('/api/bought-audios')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/bought-videos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_bought_videos_lists_the_users_purchases(): void
    {
        $user = User::factory()->create();
        $video = $this->makeVideo();
        BoughtVideo::factory()->create(['video_id' => $video->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/bought-videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id)
            ->assertJsonPath('data.0.video', 'storage/videos/song.mp4')
            ->assertJsonPath('data.0.hasBoughtVideo', true);
    }

    public function test_artist_bought_audios_lists_sales_by_username(): void
    {
        $artist = $this->musician();
        $audio = $this->makeAudio($artist);
        BoughtAudio::factory()->count(2)->create(['audio_id' => $audio->id]);
        BoughtAudio::factory()->create(['audio_id' => $this->makeAudio()->id]);

        $this->getJson('/api/artist/bought-audios/'.$artist->username)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $audio->id);

        $this->getJson('/api/artist/bought-audios/@nobody')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_artist_bought_videos_lists_sales_by_username(): void
    {
        $artist = $this->musician();
        $video = $this->makeVideo($artist);
        BoughtVideo::factory()->create(['video_id' => $video->id]);

        $this->getJson('/api/artist/bought-videos/'.$artist->username)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id);
    }

    public function test_stub_endpoints_still_resolve(): void
    {
        $user = User::factory()->create();
        $boughtAudio = BoughtAudio::factory()->create(['audio_id' => $this->makeAudio()->id]);

        $this->actingAs($user, 'sanctum')->postJson('/api/bought-audios')->assertOk()->assertContent('');
        $this->postJson('/api/bought-videos')->assertOk();
        $this->getJson('/api/bought-audios/'.$boughtAudio->id)->assertOk();
        $this->getJson('/api/bought-audios/missing')->assertNotFound();
        $this->getJson('/api/decos')->assertOk();
        $this->postJson('/api/decos')->assertOk();
        $this->getJson('/api/song-payouts')->assertOk();
        $this->postJson('/api/song-payouts', ['amount' => 100])->assertOk();
    }

    public function test_seeders_record_purchases(): void
    {
        User::factory()->count(3)->create();
        $this->makeAudio();
        $this->makeVideo();

        $this->seed([BoughtAudioSeeder::class, BoughtVideoSeeder::class]);

        $this->assertGreaterThan(0, BoughtAudio::count());
        $this->assertGreaterThan(0, BoughtVideo::count());
        $this->assertSame(BoughtAudio::first()->audio->user_id, BoughtAudio::first()->artist_id);
    }
}
