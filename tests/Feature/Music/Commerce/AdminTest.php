<?php

namespace Tests\Feature\Music\Commerce;

use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\SongPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['email' => config('admin.email')]);
    }

    public function test_music_admin_routes_are_for_the_admin_only(): void
    {
        $paths = ['admin', 'videos', 'audios', 'song-payouts'];

        foreach ($paths as $path) {
            $this->getJson('/api/admin/'.$path)->assertUnauthorized();
        }

        foreach ($paths as $path) {
            $this->actingAs(User::factory()->create(), 'sanctum')
                ->getJson('/api/admin/'.$path)
                ->assertForbidden();
        }
    }

    public function test_admin_returns_music_totals(): void
    {
        $admin = $this->admin();
        $audio = $this->makeAudio();
        $video = $this->makeVideo();
        BoughtAudio::factory()->create(['audio_id' => $audio->id]);
        BoughtVideo::factory()->create(['video_id' => $video->id]);

        // admin + 2 musicians + 2 buyers
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/admin')
            ->assertOk()
            ->assertExactJson(['data' => [
                'normal' => 3,
                'musicians' => 2,
                'users' => 5,
                'videos' => 1,
                'boughtVideos' => 1,
                'audios' => 1,
                'boughtAudios' => 1,
            ]]);
    }

    public function test_admin_lists_videos_and_audios(): void
    {
        $admin = $this->admin();
        $video = $this->makeVideo();
        $audio = $this->makeAudio();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/videos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/audios')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $audio->id);
    }

    public function test_admin_lists_song_payouts(): void
    {
        $admin = $this->admin();
        $songPayout = SongPayout::factory()->create(['amount' => '1500']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/song-payouts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $songPayout->id)
            ->assertJsonPath('data.0.username', $songPayout->user->username)
            ->assertJsonPath('data.0.amount', '1500');
    }
}
