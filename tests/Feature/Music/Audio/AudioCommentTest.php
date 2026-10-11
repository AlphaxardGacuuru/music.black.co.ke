<?php

namespace Tests\Feature\Music\Audio;

use App\Events\AudioCommentedEvent;
use App\Models\Audio;
use App\Models\AudioComment;
use App\Models\AudioCommentLike;
use App\Models\User;
use App\Notifications\AudioCommentedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_comments(): void
    {
        AudioComment::factory()->count(2)->create();

        $this->getJson('/api/audio-comments')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_the_audios_comments_paginated(): void
    {
        $comment = AudioComment::factory()->create();
        AudioCommentLike::factory()->create(['audio_comment_id' => $comment->id]);
        AudioComment::factory()->create();

        $this->getJson("/api/audio-comments/{$comment->audio_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', $comment->user->username)
            ->assertJsonPath('data.0.likes', 1)
            ->assertJsonPath('data.0.hasLiked', false)
            ->assertJsonStructure([
                'data' => [['id', 'audioId', 'text', 'username', 'name', 'avatar', 'decos', 'hasLiked', 'likes', 'createdAt']],
                'links',
                'meta',
            ]);
    }

    public function test_guests_cannot_comment(): void
    {
        $this->postJson('/api/audio-comments', [])->assertUnauthorized();
    }

    public function test_comment_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/audio-comments', [])
            ->assertJsonValidationErrors(['id', 'text']);
    }

    public function test_comment_is_stored_and_notifies_the_artist(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $audio = Audio::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/audio-comments', ['id' => $audio->id, 'text' => 'Fire'])
            ->assertOk()
            ->assertJsonPath('message', 'Comment posted');

        $this->assertDatabaseHas('audio_comments', ['audio_id' => $audio->id, 'user_id' => $user->id, 'text' => 'Fire']);

        Notification::assertSentTo($audio->user, AudioCommentedNotification::class, function ($notification) use ($audio, $user) {
            $data = $notification->toArray($audio->user);

            return $data['url'] === '/audio/show/' . $audio->id
                && $data['message'] === $user->username . ' commented on ' . $audio->name;
        });
    }

    public function test_commenting_on_own_audio_does_not_dispatch(): void
    {
        Event::fake([AudioCommentedEvent::class]);
        $audio = Audio::factory()->create();
        Sanctum::actingAs($audio->user);

        $this->postJson('/api/audio-comments', ['id' => $audio->id, 'text' => 'Mine'])->assertOk();

        Event::assertNotDispatched(AudioCommentedEvent::class);
    }

    public function test_comment_can_be_deleted(): void
    {
        $comment = AudioComment::factory()->create();
        Sanctum::actingAs($comment->user);

        $this->deleteJson("/api/audio-comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Comment deleted');

        $this->assertModelMissing($comment);
    }

    public function test_guests_cannot_delete_comments(): void
    {
        $comment = AudioComment::factory()->create();

        $this->deleteJson("/api/audio-comments/{$comment->id}")->assertUnauthorized();
    }
}
