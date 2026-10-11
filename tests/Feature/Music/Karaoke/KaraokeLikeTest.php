<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeLikeTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_user_can_like_and_unlike_karaoke(): void
    {
        $karaoke = $this->createKaraoke();
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('api/karaoke-likes', ['karaoke' => $karaoke->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Karaoke liked']);

        $this->assertDatabaseHas('karaoke_likes', [
            'karaoke_id' => $karaoke->id,
            'user_id' => $user->id,
        ]);

        $this->postJson('api/karaoke-likes', ['karaoke' => $karaoke->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Like removed']);

        $this->assertDatabaseCount('karaoke_likes', 0);
    }

    public function test_guest_cannot_like_karaoke(): void
    {
        $karaoke = $this->createKaraoke();

        $this->postJson('api/karaoke-likes', ['karaoke' => $karaoke->id])->assertUnauthorized();
    }
}
