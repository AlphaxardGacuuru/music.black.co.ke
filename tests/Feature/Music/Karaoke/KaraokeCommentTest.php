<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\KaraokeComment;
use App\Models\KaraokeCommentLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaraokeCommentTest extends TestCase
{
    use CreatesKaraokes, RefreshDatabase;

    public function test_guest_can_list_all_comments_newest_first(): void
    {
        $karaoke = $this->createKaraoke();
        $old = KaraokeComment::factory()->create(['karaoke_id' => $karaoke->id, 'created_at' => now()->subDay()]);
        $new = KaraokeComment::factory()->create(['karaoke_id' => $karaoke->id]);

        $this->getJson('api/karaoke-comments')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonStructure(['data' => [[
                'id', 'karaoke_id', 'text', 'username', 'name', 'avatar', 'hasLiked', 'likes', 'created_at',
            ]]]);
    }

    public function test_show_returns_paginated_comments_of_a_karaoke(): void
    {
        $karaoke = $this->createKaraoke();
        $other = $this->createKaraoke();
        $user = User::factory()->create();

        $comments = KaraokeComment::factory()->count(11)->create(['karaoke_id' => $karaoke->id]);
        KaraokeComment::factory()->create(['karaoke_id' => $other->id]);
        KaraokeCommentLike::factory()->create([
            'karaoke_comment_id' => $comments->first()->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('api/karaoke-comments/'.$karaoke->id)
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 11)
            ->assertJsonPath('meta.per_page', 10);

        $liked = collect($response->json('data'))->firstWhere('id', $comments->first()->id)
            ?? collect($this->getJson('api/karaoke-comments/'.$karaoke->id.'?page=2')->json('data'))
                ->firstWhere('id', $comments->first()->id);

        $this->assertTrue($liked['hasLiked']);
        $this->assertSame(1, $liked['likes']);
        $this->assertSame($comments->first()->user->username, $liked['username']);
    }

    public function test_user_can_comment_on_karaoke(): void
    {
        $karaoke = $this->createKaraoke();
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('api/karaoke-comments', ['id' => $karaoke->id, 'text' => 'Some text'])
            ->assertOk()
            ->assertJsonPath('message', 'Comment posted')
            ->assertJsonPath('data.text', 'Some text');

        $this->assertDatabaseHas('karaoke_comments', [
            'karaoke_id' => $karaoke->id,
            'text' => 'Some text',
            'user_id' => $user->id,
        ]);
    }

    public function test_comment_requires_text(): void
    {
        $karaoke = $this->createKaraoke();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('api/karaoke-comments', ['id' => $karaoke->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    public function test_guest_cannot_comment(): void
    {
        $karaoke = $this->createKaraoke();

        $this->postJson('api/karaoke-comments', ['id' => $karaoke->id, 'text' => 'Hi'])
            ->assertUnauthorized();
    }

    public function test_user_can_delete_comment(): void
    {
        $comment = KaraokeComment::factory()->create(['karaoke_id' => $this->createKaraoke()->id]);
        Sanctum::actingAs($comment->user);

        $this->deleteJson('api/karaoke-comments/'.$comment->id)
            ->assertOk()
            ->assertExactJson(['message' => 'Comment deleted']);

        $this->assertModelMissing($comment);
    }
}
