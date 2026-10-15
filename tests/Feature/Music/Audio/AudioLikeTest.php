<?php

namespace Tests\Feature\Music\Audio;

use App\Events\AudioLikedEvent;
use App\Models\Audio;
use App\Models\User;
use App\Notifications\AudioLikedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AudioLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_like(): void
    {
        $this->postJson('/api/audio-likes', [])->assertUnauthorized();
    }

    public function test_like_requires_an_existing_audio(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/audio-likes', ['audio' => 'missing'])
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_like_toggles_and_notifies_the_artist(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $audio = Audio::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/audio-likes', ['audio' => $audio->id])
            ->assertOk()
            ->assertJsonPath('message', 'Audio liked');

        $this->assertDatabaseHas('audio_likes', ['audio_id' => $audio->id, 'user_id' => $user->id]);

        Notification::assertSentTo($audio->user, AudioLikedNotification::class, function ($notification) use ($audio, $user) {
            $data = $notification->toArray($audio->user);

            return $data['url'] === '/audios/' . $audio->id . '/view'
                && $data['from'] === $user->username
                && $data['message'] === $user->username . ' liked ' . $audio->name
                && $notification->via($audio->user) === ['database', 'broadcast'];
        });

        $this->postJson('/api/audio-likes', ['audio' => $audio->id])
            ->assertOk()
            ->assertJsonPath('message', 'Like removed');

        $this->assertDatabaseMissing('audio_likes', ['audio_id' => $audio->id, 'user_id' => $user->id]);
        Notification::assertSentToTimes($audio->user, AudioLikedNotification::class, 1);
    }

    public function test_liking_own_audio_does_not_dispatch(): void
    {
        Event::fake([AudioLikedEvent::class]);
        $audio = Audio::factory()->create();
        Sanctum::actingAs($audio->user);

        $this->postJson('/api/audio-likes', ['audio' => $audio->id])->assertOk();

        Event::assertNotDispatched(AudioLikedEvent::class);
    }
}
