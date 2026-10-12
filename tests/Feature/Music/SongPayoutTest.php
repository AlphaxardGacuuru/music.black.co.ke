<?php

namespace Tests\Feature\Music;

use App\Events\KopokopoTransferRecordedEvent;
use App\Http\Services\KopokopoTransferService;
use App\Listeners\KopokopoTransferRecordedListener;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\KopokopoTransfer;
use App\Models\SongPayout;
use App\Models\User;
use App\Notifications\SongPayoutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\Feature\Music\Commerce\CreatesSongs;
use Tests\TestCase;

class SongPayoutTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = $this->musician(['phone' => '0712345678', 'withdrawal' => '10']);
    }

    /**
     * Record $videos video sales and $audios audio sales for the artist.
     */
    private function sell(int $videos, int $audios = 0): void
    {
        for ($i = 0; $i < $videos; $i++) {
            BoughtVideo::create([
                'video_id' => $this->makeVideo($this->artist)->id,
                'user_id' => User::factory()->create()->id,
                'artist_id' => $this->artist->id,
                'price' => '20',
            ]);
        }

        for ($i = 0; $i < $audios; $i++) {
            BoughtAudio::create([
                'audio_id' => $this->makeAudio($this->artist)->id,
                'user_id' => User::factory()->create()->id,
                'artist_id' => $this->artist->id,
                'price' => '10',
            ]);
        }
    }

    /**
     * Fake Kopokopo accepting (or refusing) the transfer.
     */
    private function fakeTransfer(bool $accepted = true): Mockery\MockInterface
    {
        $transfers = Mockery::mock(KopokopoTransferService::class)->makePartial();
        $transfers->shouldReceive('initiateTransfer')->andReturn(
            $accepted
                ? [true, 'Transfer initiated', ['location' => 'https://k2/api/v1/transfers/ref-123']]
                : [false, 'Kopokopo Transfer Failed', []]
        );
        $this->app->instance(KopokopoTransferService::class, $transfers);

        return $transfers;
    }

    private function confirmTransfer(string $status): void
    {
        $transfer = new KopokopoTransfer;
        $transfer->kopokopo_id = 'ref-123';
        $transfer->status = $status;

        (new KopokopoTransferRecordedListener)->handle(new KopokopoTransferRecordedEvent($transfer));
    }

    public function test_artists_earn_half_of_every_sale(): void
    {
        $this->sell(videos: 2, audios: 3);

        // (2 x 20 + 3 x 10) / 2
        $this->actingAs($this->artist, 'sanctum')
            ->getJson('/api/song-payouts')
            ->assertOk()
            ->assertJsonPath('data.balance', 35)
            ->assertJsonPath('data.songPayouts', []);
    }

    public function test_a_small_withdrawal_sends_the_balance_less_the_fee(): void
    {
        $this->sell(videos: 10);
        $transfers = $this->fakeTransfer();

        $this->actingAs($this->artist, 'sanctum')
            ->postJson('/api/song-payouts')
            ->assertOk()
            ->assertJsonPath('message', 'KES 50 is on its way to 0712345678');

        $transfers->shouldHaveReceived('initiateTransfer')
            ->withArgs(fn($request) => (int) $request->input('amount') === 50);

        $payout = SongPayout::sole();
        $this->assertSame('100', $payout->amount);
        $this->assertSame(50, $payout->fee);
        $this->assertSame('ref-123', $payout->kopokopo_reference);
        $this->assertSame(0, SongPayout::balanceFor($this->artist));
    }

    public function test_withdrawals_of_1000_or_more_have_no_fee(): void
    {
        $this->artist->update(['withdrawal' => '1000']);
        $this->sell(videos: 100);
        $transfers = $this->fakeTransfer();

        $this->actingAs($this->artist, 'sanctum')
            ->postJson('/api/song-payouts')
            ->assertOk();

        $transfers->shouldHaveReceived('initiateTransfer')
            ->withArgs(fn($request) => (int) $request->input('amount') === 1000);
        $this->assertSame(0, SongPayout::sole()->fee);
    }

    public function test_artists_cannot_withdraw_below_their_minimum(): void
    {
        $this->artist->update(['withdrawal' => '1000']);
        $this->sell(videos: 10);
        $this->fakeTransfer();

        $this->actingAs($this->artist, 'sanctum')
            ->postJson('/api/song-payouts')
            ->assertStatus(422);

        $this->assertSame(0, SongPayout::count());
    }

    public function test_a_payout_in_flight_blocks_another(): void
    {
        $this->sell(videos: 10);
        $this->fakeTransfer();

        $this->actingAs($this->artist, 'sanctum')->postJson('/api/song-payouts')->assertOk();
        $this->sell(videos: 10);
        $this->actingAs($this->artist, 'sanctum')->postJson('/api/song-payouts')->assertStatus(422);

        $this->assertSame(1, SongPayout::count());
    }

    public function test_a_refused_transfer_keeps_the_balance(): void
    {
        $this->sell(videos: 10);
        $this->fakeTransfer(accepted: false);

        $this->actingAs($this->artist, 'sanctum')
            ->postJson('/api/song-payouts')
            ->assertStatus(422);

        $this->assertSame(0, SongPayout::count());
        $this->assertSame(100, SongPayout::balanceFor($this->artist));
    }

    public function test_a_confirmed_transfer_marks_the_payout_paid_and_tells_the_artist(): void
    {
        Notification::fake();
        $this->sell(videos: 10);
        $this->fakeTransfer();
        $this->actingAs($this->artist, 'sanctum')->postJson('/api/song-payouts')->assertOk();

        $this->confirmTransfer('Transferred');

        $this->assertNotNull(SongPayout::sole()->paid_at);
        Notification::assertSentTo($this->artist, SongPayoutNotification::class);
    }

    public function test_a_failed_transfer_returns_the_money_to_the_balance(): void
    {
        $this->sell(videos: 10);
        $this->fakeTransfer();
        $this->actingAs($this->artist, 'sanctum')->postJson('/api/song-payouts')->assertOk();

        $this->confirmTransfer('Failed');

        $this->assertSame(0, SongPayout::count());
        $this->assertSame(100, SongPayout::balanceFor($this->artist));
    }

    public function test_guests_cannot_see_or_request_payouts(): void
    {
        $this->getJson('/api/song-payouts')->assertUnauthorized();
        $this->postJson('/api/song-payouts')->assertUnauthorized();
    }
}
