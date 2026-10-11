<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\SavedKaraoke;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedKaraokeTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_user_can_save_and_unsave_karaoke(): void
    {
        $karaoke = $this->createKaraoke();
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('api/saved-karaokes', ['id' => $karaoke->id])
            ->assertOk()
            ->assertJsonPath('message', 'Karaoke saved')
            ->assertJsonPath('data.karaoke_id', $karaoke->id);

        $this->assertDatabaseHas('saved_karaokes', [
            'karaoke_id' => $karaoke->id,
            'user_id' => $user->id,
        ]);

        $this->postJson('api/saved-karaokes', ['id' => $karaoke->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Karaoke removed', 'data' => 'data']);

        $this->assertDatabaseCount('saved_karaokes', 0);
    }

    public function test_index_lists_only_current_users_saved_karaokes(): void
    {
        $mine = $this->createKaraoke();
        $theirs = $this->createKaraoke();
        $user = User::factory()->create();

        SavedKaraoke::factory()->create(['karaoke_id' => $mine->id, 'user_id' => $user->id]);
        SavedKaraoke::factory()->create(['karaoke_id' => $theirs->id]);

        Sanctum::actingAs($user);

        $this->getJson('api/saved-karaokes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.username', $mine->user->username)
            ->assertJsonPath('data.0.hasSaved', true)
            ->assertJsonStructure(['data' => [[
                'id', 'karaoke', 'audioId', 'audioName', 'audioThumbnail', 'name', 'username',
                'avatar', 'decos', 'description', 'hasLiked', 'hasSaved', 'likes', 'comments', 'createdAt',
            ]]]);
    }

    public function test_guest_gets_empty_saved_karaokes(): void
    {
        SavedKaraoke::factory()->create(['karaoke_id' => $this->createKaraoke()->id]);

        $this->getJson('api/saved-karaokes')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_save_requires_valid_id(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('api/saved-karaokes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_guest_cannot_save_karaoke(): void
    {
        $this->postJson('api/saved-karaokes', ['id' => $this->createKaraoke()->id])->assertUnauthorized();
    }
}
