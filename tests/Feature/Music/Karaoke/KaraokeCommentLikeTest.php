<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\KaraokeComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeCommentLikeTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_user_can_like_and_unlike_karaoke_comment(): void
    {
        $comment = KaraokeComment::factory()->create(['karaoke_id' => $this->createKaraoke()->id]);
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('api/karaoke-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Comment liked']);

        $this->assertDatabaseHas('karaoke_comment_likes', [
            'karaoke_comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        $this->postJson('api/karaoke-comment-likes', ['comment' => $comment->id])
            ->assertOk()
            ->assertExactJson(['message' => 'Like removed']);

        $this->assertDatabaseCount('karaoke_comment_likes', 0);
    }

    public function test_guest_cannot_like_karaoke_comment(): void
    {
        $comment = KaraokeComment::factory()->create(['karaoke_id' => $this->createKaraoke()->id]);

        $this->postJson('api/karaoke-comment-likes', ['comment' => $comment->id])->assertUnauthorized();
    }
}
