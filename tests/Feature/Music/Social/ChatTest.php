<?php

namespace Tests\Feature\Music\Social;

use App\Events\ChatDeletedEvent;
use App\Events\NewChatEvent;
use App\Models\Chat;
use App\Models\User;
use App\Notifications\NewChatNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_latest_message_per_thread(): void
    {
        $me = User::factory()->create();
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Chat::factory()->create(['user_id' => $me->id, 'to_id' => $alice->id, 'text' => 'first']);
        $latestAlice = Chat::factory()->create(['user_id' => $alice->id, 'to_id' => $me->id, 'text' => 'reply']);
        Chat::factory()->create(['user_id' => $me->id, 'to_id' => $bob->id, 'text' => 'hi bob']);
        Chat::factory()->create(['user_id' => $alice->id, 'to_id' => $bob->id]);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/chats')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.username', $bob->username)
            ->assertJsonPath('data.0.text', 'hi bob')
            ->assertJsonPath('data.0.to', $bob->username)
            ->assertJsonStructure(['data' => [['id', 'avatar', 'name', 'username', 'to', 'text', 'hasMedia', 'createdAt']]]);

        $this->assertSame($latestAlice->id, $response->json('data.1.id'));
        $this->assertSame($me->username, $response->json('data.1.to'));
    }

    public function test_show_lists_conversation_with_username(): void
    {
        $me = User::factory()->create();
        $alice = User::factory()->create();
        Chat::factory()->create(['user_id' => $me->id, 'to_id' => $alice->id]);
        Chat::factory()->create(['user_id' => $alice->id, 'to_id' => $me->id]);
        Chat::factory()->create(['user_id' => $alice->id]);

        Sanctum::actingAs($me);

        $this->getJson("/api/chats/{$alice->username}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.username', $me->username)
            ->assertJsonPath('data.0.to', $alice->username)
            ->assertJsonStructure(['data' => [['id', 'name', 'username', 'to', 'avatar', 'decos', 'text', 'media', 'createdAt']]]);
    }

    public function test_store_requires_auth_and_validates(): void
    {
        $this->postJson('/api/chats', [])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/chats', ['to' => '@nobody'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'to']);
    }

    public function test_user_can_send_chat_which_broadcasts_and_notifies_recipient(): void
    {
        Notification::fake();
        $me = User::factory()->create();
        $alice = User::factory()->create();
        Sanctum::actingAs($me);

        $this->postJson('/api/chats', ['to' => $alice->username, 'text' => 'Yo', 'media' => 'chat-media/a.jpg'])
            ->assertOk()
            ->assertJsonPath('message', 'Chat sent');

        $chat = Chat::firstWhere('text', 'Yo');
        $this->assertSame($me->id, $chat->user_id);
        $this->assertSame($alice->id, $chat->to_id);
        $this->assertSame('/storage/chat-media/a.jpg', $chat->media);

        Notification::assertSentTo($alice, NewChatNotification::class, function ($n) use ($alice, $me) {
            return $n->toArray($alice) === [
                'url' => '/chat/' . $me->username . '/view',
                'from' => $me->username,
                'message' => $me->username.' sent you a message: Yo',
            ];
        });
    }

    public function test_new_chat_event_broadcasts_on_chat_created(): void
    {
        Event::fake([NewChatEvent::class]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/chats', ['to' => User::factory()->create()->username, 'text' => 'Yo'])->assertOk();

        Event::assertDispatched(NewChatEvent::class, fn (NewChatEvent $event) => $event->broadcastOn()[0] == new PrivateChannel('chat-created'));
    }

    public function test_user_can_delete_chat(): void
    {
        Event::fake([ChatDeletedEvent::class]);
        Storage::fake('public');
        Storage::disk('public')->put('chat-media/a.jpg', 'x');
        $chat = Chat::factory()->create(['media' => 'chat-media/a.jpg']);
        Sanctum::actingAs($chat->user);

        $this->deleteJson("/api/chats/{$chat->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Chat deleted');

        $this->assertSoftDeleted($chat);
        Storage::disk('public')->assertMissing('chat-media/a.jpg');
        Event::assertDispatched(ChatDeletedEvent::class, fn (ChatDeletedEvent $event) => $event->chat === $chat->id
            && $event->broadcastOn()[0] == new PrivateChannel('chat-deleted'));
    }
}
