<?php

namespace Tests\Feature\Music\Commerce;

use App\Events\AudioBoughtEvent;
use App\Events\VideoBoughtEvent;
use App\Models\BoughtAudio;
use App\Models\CartAudio;
use App\Models\CartVideo;
use App\Models\MPESATransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    public function test_users_can_buy_their_cart_with_the_balance_they_have(): void
    {
        Event::fake([VideoBoughtEvent::class, AudioBoughtEvent::class]);

        $fan = User::factory()->create();
        MPESATransaction::factory()->for($fan)->create(['amount' => '30']);
        CartVideo::create(['user_id' => $fan->id, 'video_id' => $this->makeVideo()->id]);
        CartAudio::create(['user_id' => $fan->id, 'audio_id' => $this->makeAudio()->id]);

        $this->actingAs($fan, 'sanctum')
            ->postJson('/api/cart-checkout')
            ->assertOk()
            ->assertJsonPath('message', '2 songs bought')
            ->assertJsonCount(1, 'data.videos')
            ->assertJsonCount(1, 'data.audios');

        $this->assertSame(0, $fan->fresh()->balance());
        $this->assertSame(0, CartVideo::count() + CartAudio::count());
        Event::assertDispatched(VideoBoughtEvent::class);
        Event::assertDispatched(AudioBoughtEvent::class);
    }

    public function test_checkout_buys_nothing_the_balance_does_not_cover(): void
    {
        $fan = User::factory()->create();
        MPESATransaction::factory()->for($fan)->create(['amount' => '5']);
        CartAudio::create(['user_id' => $fan->id, 'audio_id' => $this->makeAudio()->id]);

        $this->actingAs($fan, 'sanctum')
            ->postJson('/api/cart-checkout')
            ->assertStatus(422);

        $this->assertSame(0, BoughtAudio::count());
        $this->assertSame(1, CartAudio::count());
    }

    public function test_guests_cannot_check_out(): void
    {
        $this->postJson('/api/cart-checkout')->assertUnauthorized();
    }

    public function test_purchase_broadcasts_go_to_the_buyer_only(): void
    {
        $fan = User::factory()->create();

        $event = new AudioBoughtEvent([], [], [], $fan);

        $this->assertSame('private-audio-bought.' . $fan->id, $event->broadcastOn()->name);
        $this->assertSame(
            'private-video-bought.' . $fan->id,
            (new VideoBoughtEvent([], [], [], $fan))->broadcastOn()->name
        );
    }
}
