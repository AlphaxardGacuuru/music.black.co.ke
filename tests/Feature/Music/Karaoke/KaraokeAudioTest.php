<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\KaraokeAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeAudioTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_guest_can_list_karaoke_audios(): void
    {
        $karaokeAudio = KaraokeAudio::factory()->create(['audio_id' => $this->createAudio()->id]);

        $this->getJson('api/karaoke-audios')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $karaokeAudio->id)
            ->assertJsonPath('0.audio_id', $karaokeAudio->audio_id)
            ->assertJsonPath('0.audio.name', 'Song Name')
            ->assertJsonPath('0.audio.thumbnail', '/storage/audio-thumbnails/song.png');
    }

    public function test_guest_can_view_one_karaoke_audio(): void
    {
        $karaokeAudio = KaraokeAudio::factory()->create(['audio_id' => $this->createAudio()->id]);

        $this->getJson('api/karaoke-audios/'.$karaokeAudio->id)
            ->assertOk()
            ->assertJsonPath('data.id', $karaokeAudio->id)
            ->assertJsonPath('data.audioId', $karaokeAudio->audio_id)
            ->assertJsonPath('data.username', $karaokeAudio->user->username)
            ->assertJsonPath('data.name', 'Song Name')
            ->assertJsonPath('data.thumbnail', '/storage/audio-thumbnails/song.png');
    }

    public function test_user_can_store_karaoke_audio(): void
    {
        $audio = $this->createAudio();
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('api/karaoke-audios', ['audio_id' => $audio->id])
            ->assertOk()
            ->assertJsonPath('message', 'Audio saved');

        $this->assertDatabaseHas('karaoke_audios', [
            'audio_id' => $audio->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_store_karaoke_audio_validates_audio(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('api/karaoke-audios', ['audio_id' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['audio_id']);
    }

    public function test_guest_cannot_store_karaoke_audio(): void
    {
        $this->postJson('api/karaoke-audios', ['audio_id' => $this->createAudio()->id])
            ->assertUnauthorized();
    }
}
