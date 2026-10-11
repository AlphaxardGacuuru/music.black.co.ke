<?php

namespace Tests\Feature\Music\Audio;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\AudioLike;
use App\Models\BoughtAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_audios(): void
    {
        Audio::factory()->count(2)->create();

        $this->getJson('/api/audios')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_the_old_resource_shape(): void
    {
        $ft = User::factory()->create();
        $audio = Audio::factory()->create(['ft_id' => $ft->id]);

        $this->getJson("/api/audios/{$audio->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $audio->id)
            ->assertJsonPath('data.username', $audio->user->username)
            ->assertJsonPath('data.ft', $ft->username)
            ->assertJsonPath('data.album', $audio->album->name)
            ->assertJsonPath('data.hasLiked', false)
            ->assertJsonStructure(['data' => [
                'id', 'audio', 'name', 'artistName', 'username', 'avatar', 'artistDecos',
                'ft', 'audioAlbumId', 'album', 'genre', 'thumbnail', 'description',
                'released', 'hasLiked', 'likes', 'comments', 'inCart', 'hasBoughtAudio',
                'hasBought1', 'hasFollowed', 'downloads', 'createdAt',
            ]]);
    }

    public function test_show_reflects_the_current_user(): void
    {
        $user = User::factory()->create();
        $audio = Audio::factory()->create();
        AudioLike::factory()->create(['audio_id' => $audio->id, 'user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/audios/{$audio->id}")
            ->assertJsonPath('data.hasLiked', true)
            ->assertJsonPath('data.likes', 1);
    }

    public function test_show_returns_404_for_missing_audio(): void
    {
        $this->getJson('/api/audios/missing')->assertNotFound();
    }

    public function test_guests_cannot_store_audio(): void
    {
        $this->postJson('/api/audios', [])->assertUnauthorized();
    }

    public function test_store_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/audios', ['ft' => '@nobody'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['audio', 'name', 'thumbnail', 'ft', 'audio_album_id', 'genre']);
    }

    public function test_user_can_store_audio(): void
    {
        $user = User::factory()->create();
        $ft = User::factory()->create();
        $album = AudioAlbum::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/audios', [
            'audio' => 'audios/song.mp3',
            'name' => 'Song',
            'thumbnail' => 'audio-thumbnails/song.jpg',
            'ft' => $ft->username,
            'audio_album_id' => $album->id,
            'genre' => 'Afro',
            'description' => 'Nice',
            'released' => '2026-01-01',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Audio Uploaded');

        $this->assertDatabaseHas('audios', [
            'name' => 'Song',
            'user_id' => $user->id,
            'ft_id' => $ft->id,
            'audio_album_id' => $album->id,
            'audio' => 'audios/song.mp3',
        ]);
    }

    public function test_user_can_update_audio_and_old_files_are_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audio-thumbnails/old.jpg', 'x');
        Storage::disk('public')->put('audios/old.mp3', 'x');

        $ft = User::factory()->create();
        $audio = Audio::factory()->create([
            'thumbnail' => 'audio-thumbnails/old.jpg',
            'audio' => 'audios/old.mp3',
        ]);

        Sanctum::actingAs($audio->user);

        $this->putJson("/api/audios/{$audio->id}", [
            'name' => 'Renamed',
            'ft' => $ft->username,
            'thumbnail' => 'audio-thumbnails/new.jpg',
            'audio' => 'audios/new.mp3',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Renamed edited');

        $audio->refresh();
        $this->assertSame('Renamed', $audio->name);
        $this->assertSame($ft->id, $audio->ft_id);
        $this->assertSame('audio-thumbnails/new.jpg', $audio->getRawOriginal('thumbnail'));
        Storage::disk('public')->assertMissing('audio-thumbnails/old.jpg');
        Storage::disk('public')->assertMissing('audios/old.mp3');
    }

    public function test_download_returns_the_file_by_path_and_query(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audios/song.mp3', 'mp3-bytes');

        $audio = Audio::factory()->create(['audio' => 'audios/song.mp3', 'name' => 'My Song']);

        $this->get("/api/audios/download/{$audio->id}")
            ->assertOk()
            ->assertDownload('My Song.mp3');

        $this->get("/api/audios/download?id={$audio->id}")
            ->assertOk()
            ->assertDownload('My Song.mp3');
    }

    public function test_download_returns_404_without_a_file(): void
    {
        Storage::fake('public');

        $audio = Audio::factory()->create();

        $this->get("/api/audios/download/{$audio->id}")->assertNotFound();
        $this->get('/api/audios/download')->assertNotFound();
    }

    public function test_artist_audios(): void
    {
        $audio = Audio::factory()->create();
        Audio::factory()->create();

        $this->getJson("/api/artist/audios/{$audio->user->username}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $audio->id);
    }

    public function test_newly_released_chart(): void
    {
        $audio = Audio::factory()->create();

        $this->getJson('/api/audio-charts/newly-released')
            ->assertOk()
            ->assertJsonCount(1, 'data.audios')
            ->assertJsonPath('data.audios.0.id', $audio->id)
            ->assertJsonPath('data.artists.0.username', $audio->user->username);
    }

    public function test_top_liked_chart_orders_by_likes(): void
    {
        $popular = Audio::factory()->create();
        $other = Audio::factory()->create();
        AudioLike::factory()->count(2)->create(['audio_id' => $popular->id]);
        AudioLike::factory()->create(['audio_id' => $other->id]);

        $this->getJson('/api/audio-charts/top-liked')
            ->assertOk()
            ->assertJsonPath('data.audios.0.id', $popular->id)
            ->assertJsonPath('data.audios.1.id', $other->id)
            ->assertJsonCount(2, 'data.artists');
    }

    public function test_trending_and_top_downloaded_charts_use_bought_audios(): void
    {
        $audio = Audio::factory()->create();
        $buyer = User::factory()->create();
        BoughtAudio::create([
            'audio_id' => $audio->id,
            'user_id' => $buyer->id,
            'artist_id' => $audio->user_id,
            'price' => BoughtAudio::PRICE,
        ]);

        $this->getJson('/api/audio-charts/trending')
            ->assertOk()
            ->assertJsonPath('data.audios.0.id', $audio->id)
            ->assertJsonPath('data.audios.0.downloads', 1);

        $this->getJson('/api/audio-charts/top-downloaded')
            ->assertOk()
            ->assertJsonPath('data.audios.0.id', $audio->id);
    }
}
