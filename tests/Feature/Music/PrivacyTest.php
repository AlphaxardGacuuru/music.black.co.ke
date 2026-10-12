<?php

namespace Tests\Feature\Music;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\BoughtAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profiles_hide_contact_details_and_the_wallet(): void
    {
        $artist = User::factory()->musician()->create(['username' => '@artist', 'phone' => '0712345678']);

        $this->getJson('/api/users/@artist')
            ->assertOk()
            ->assertJsonPath('data.username', '@artist')
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.balance')
            ->assertJsonMissingPath('data.settings');

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/users/@artist')
            ->assertJsonMissingPath('data.phone');
    }

    public function test_users_see_their_own_private_details(): void
    {
        $user = User::factory()->create(['phone' => '0712345678']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth')
            ->assertOk()
            ->assertJsonPath('data.phone', '0712345678')
            ->assertJsonPath('data.balance', 0);
    }

    public function test_only_buyers_and_the_artist_can_download_a_song(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audios/song.mp3', 'song');

        $artist = User::factory()->musician()->create();
        $audio = Audio::factory()->for($artist)->create([
            'audio_album_id' => AudioAlbum::factory()->for($artist),
            'audio' => 'audios/song.mp3',
            'name' => 'Song',
        ]);
        $buyer = User::factory()->create();
        BoughtAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id, 'artist_id' => $artist->id, 'price' => '10']);

        $this->getJson("/api/audios/download/{$audio->id}")->assertUnauthorized();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/audios/download/{$audio->id}")
            ->assertForbidden();
    }

    public function test_the_buyer_can_download_a_song(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('audios/song.mp3', 'song');

        $artist = User::factory()->musician()->create();
        $audio = Audio::factory()->for($artist)->create([
            'audio_album_id' => AudioAlbum::factory()->for($artist),
            'audio' => 'audios/song.mp3',
            'name' => 'Song',
        ]);
        $buyer = User::factory()->create();
        BoughtAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id, 'artist_id' => $artist->id, 'price' => '10']);

        $this->actingAs($buyer, 'sanctum')
            ->get("/api/audios/download/{$audio->id}")
            ->assertOk()
            ->assertDownload('Song.mp3');
    }
}
