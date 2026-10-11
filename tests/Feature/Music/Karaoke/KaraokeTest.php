<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\KaraokeComment;
use App\Models\KaraokeLike;
use App\Models\SavedKaraoke;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_guest_can_list_karaokes_in_old_shape(): void
    {
        $first = $this->createKaraoke(['created_at' => now()->subDay()]);
        $second = $this->createKaraoke();

        $response = $this->getJson('api/karaokes');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id', 'karaoke', 'audioId', 'audioName', 'audioThumbnail', 'name',
                'username', 'avatar', 'decos', 'description', 'hasLiked', 'hasSaved',
                'likes', 'comments', 'created_at',
            ]]])
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.0.username', $first->user->username)
            ->assertJsonPath('data.0.karaoke', '/storage/'.$first->getRawOriginal('karaoke'))
            ->assertJsonPath('data.0.audioName', 'Song Name')
            ->assertJsonPath('data.0.hasLiked', false)
            ->assertJsonPath('data.0.hasSaved', false);
    }

    public function test_karaoke_shows_counts_and_flags_for_current_user(): void
    {
        $karaoke = $this->createKaraoke();
        $user = User::factory()->create();

        KaraokeLike::factory()->create(['karaoke_id' => $karaoke->id, 'user_id' => $user->id]);
        SavedKaraoke::factory()->create(['karaoke_id' => $karaoke->id, 'user_id' => $user->id]);
        KaraokeComment::factory()->count(2)->create(['karaoke_id' => $karaoke->id]);

        Sanctum::actingAs($user);

        $this->getJson('api/karaokes/'.$karaoke->id)
            ->assertOk()
            ->assertJsonPath('data.id', $karaoke->id)
            ->assertJsonPath('data.description', $karaoke->description)
            ->assertJsonPath('data.hasLiked', true)
            ->assertJsonPath('data.hasSaved', true)
            ->assertJsonPath('data.likes', 1)
            ->assertJsonPath('data.comments', 2);
    }

    public function test_show_missing_karaoke_returns_404(): void
    {
        $this->getJson('api/karaokes/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }

    public function test_user_can_create_karaoke(): void
    {
        $user = User::factory()->create();
        $audio = $this->createAudio();

        Sanctum::actingAs($user);

        $this->postJson('api/karaokes', [
            'karaoke' => 'karaokes/abc.mp4',
            'audio_id' => $audio->id,
            'description' => 'Some description',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Karaoke posted')
            ->assertJsonPath('data.karaoke', '/storage/karaokes/abc.mp4');

        $this->assertDatabaseHas('karaokes', [
            'karaoke' => 'karaokes/abc.mp4',
            'audio_id' => $audio->id,
            'user_id' => $user->id,
            'description' => 'Some description',
        ]);
    }

    public function test_create_karaoke_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('api/karaokes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['karaoke', 'audio_id', 'description']);
    }

    public function test_guest_cannot_create_karaoke(): void
    {
        $this->postJson('api/karaokes', [
            'karaoke' => 'karaokes/abc.mp4',
            'audio_id' => $this->createAudio()->id,
            'description' => 'Some description',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('karaokes', 0);
    }
}
