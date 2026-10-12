<?php

namespace Tests\Feature\Music\Commerce;

use App\Events\AudioBoughtEvent;
use App\Events\MpesaTransactionCreatedEvent;
use App\Events\VideoBoughtEvent;
use App\Listeners\BuyCartListener;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\CartAudio;
use App\Models\CartVideo;
use App\Models\Deco;
use App\Models\User;
use App\Notifications\AudioReceiptNotification;
use App\Notifications\BoughtAudioNotification;
use App\Notifications\BoughtVideoNotification;
use App\Notifications\DecoNotification;
use App\Notifications\MpesaTransactionCreatedNotification;
use App\Notifications\VideoReceiptNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesSongs, RefreshDatabase;

    protected function buyer(): User
    {
        return User::factory()->create(['phone' => '0712345678']);
    }

    public function test_buy_cart_listener_handles_mpesa_transactions(): void
    {
        Event::fake();

        Event::assertListening(MpesaTransactionCreatedEvent::class, BuyCartListener::class);
    }

    public function test_a_payment_buys_the_cart_and_notifies_everyone(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $artist = $this->musician();
        $audio = $this->makeAudio($artist);
        $video = $this->makeVideo($artist);
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);
        CartVideo::create(['video_id' => $video->id, 'user_id' => $buyer->id]);

        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 30))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('mpesa_transactions', ['user_id' => $buyer->id, 'amount' => 30]);
        $this->assertDatabaseHas('bought_audios', [
            'audio_id' => $audio->id,
            'user_id' => $buyer->id,
            'artist_id' => $artist->id,
            'price' => '10',
        ]);
        $this->assertDatabaseHas('bought_videos', [
            'video_id' => $video->id,
            'user_id' => $buyer->id,
            'artist_id' => $artist->id,
            'price' => '20',
        ]);
        $this->assertDatabaseCount('cart_audios', 0);
        $this->assertDatabaseCount('cart_videos', 0);

        // NEW's own payment notification is kept, not duplicated
        Notification::assertSentToTimes($buyer, MpesaTransactionCreatedNotification::class, 1);

        Notification::assertSentTo($artist, BoughtAudioNotification::class, function ($notification) use ($artist, $audio) {
            return $notification->toArray($artist) === [
                'url' => '/profile/show/'.$artist->username,
                'from' => $artist->username,
                'id' => $artist->username,
                'message' => $artist->username.' bought '.$audio->name,
            ];
        });
        Notification::assertSentTo($artist, BoughtVideoNotification::class);
        Notification::assertSentTo($buyer, AudioReceiptNotification::class, function ($notification) use ($buyer, $audio) {
            return $notification->via($buyer) === ['database', 'mail', 'broadcast']
                && $notification->toArray($buyer)['message'] === 'You bought 1 audio: '.$audio->name;
        });
        Notification::assertSentTo($buyer, VideoReceiptNotification::class, function ($notification) use ($buyer, $video) {
            return $notification->toArray($buyer) === [
                'url' => '/library',
                'from' => '@blackmusic',
                'message' => 'You bought 1 video: '.$video->name,
            ];
        });
        Notification::assertNotSentTo($buyer, DecoNotification::class);
    }

    public function test_bought_events_carry_the_purchase(): void
    {
        Event::fake([VideoBoughtEvent::class, AudioBoughtEvent::class]);
        Notification::fake();

        $buyer = $this->buyer();
        $audios = [$this->makeAudio(), $this->makeAudio()];
        foreach ($audios as $audio) {
            CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);
        }

        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 20))->assertOk();

        Event::assertDispatched(AudioBoughtEvent::class, function (AudioBoughtEvent $event) use ($buyer) {
            return $event->user->is($buyer)
                && count($event->audios) === 2
                && $event->structuredAudios->count() === 2
                && $event->decoArtists === []
                && $event->broadcastOn()->name === 'private-audio-bought.' . $buyer->id;
        });
        Event::assertNotDispatched(VideoBoughtEvent::class);
    }

    public function test_only_what_the_balance_covers_is_bought(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $video = $this->makeVideo();
        $audio = $this->makeAudio();
        CartVideo::create(['video_id' => $video->id, 'user_id' => $buyer->id]);
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);

        // KES 15 covers the audio (10) but not the video (20)
        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 15))->assertOk();

        $this->assertDatabaseCount('bought_videos', 0);
        $this->assertDatabaseHas('bought_audios', ['audio_id' => $audio->id, 'user_id' => $buyer->id]);
        $this->assertDatabaseHas('cart_videos', ['video_id' => $video->id, 'user_id' => $buyer->id]);
        Notification::assertNotSentTo($buyer, VideoReceiptNotification::class);

        // Topping up 15 more (balance now 20) buys the video
        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 15, 'QKX999ZZZ'))->assertOk();

        $this->assertDatabaseHas('bought_videos', ['video_id' => $video->id, 'user_id' => $buyer->id]);
        $this->assertDatabaseCount('cart_videos', 0);
    }

    public function test_previous_purchases_count_against_the_balance(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $this->pay($buyer, 20);
        BoughtVideo::factory()->create(['video_id' => $this->makeVideo()->id, 'user_id' => $buyer->id]);

        $audio = $this->makeAudio();
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);

        // 20 + 5 paid, 20 spent: 5 left, not enough for an audio
        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 5))->assertOk();

        $this->assertDatabaseCount('bought_audios', 0);
        $this->assertDatabaseHas('cart_audios', ['audio_id' => $audio->id]);
    }

    public function test_a_duplicate_callback_does_not_buy_twice(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $audio = $this->makeAudio();
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);

        $payload = $this->kopokopoCallback($buyer, 10);

        $this->postJson('/api/mpesa-transactions', $payload)->assertOk();

        // The song is added to the cart again, then Kopokopo repeats the callback
        CartAudio::create(['audio_id' => $this->makeAudio()->id, 'user_id' => $buyer->id]);

        $this->postJson('/api/mpesa-transactions', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Transaction Already Exists');

        $this->assertDatabaseCount('mpesa_transactions', 1);
        $this->assertDatabaseCount('bought_audios', 1);
        $this->assertDatabaseCount('cart_audios', 1);
        Notification::assertSentToTimes($buyer, AudioReceiptNotification::class, 1);
    }

    public function test_a_song_already_bought_is_not_bought_again(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $audio = $this->makeAudio();
        BoughtAudio::factory()->create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);

        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 100))->assertOk();

        $this->assertDatabaseCount('bought_audios', 1);
        Notification::assertNotSentTo($buyer, AudioReceiptNotification::class);
    }

    public function test_every_tenth_song_from_an_artist_earns_a_deco(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $artist = $this->musician();

        // 9 already bought from this artist
        $this->pay($buyer, 90);
        foreach (range(1, 9) as $i) {
            BoughtAudio::factory()->create(['audio_id' => $this->makeAudio($artist)->id, 'user_id' => $buyer->id]);
        }

        $audio = $this->makeAudio($artist);
        CartAudio::create(['audio_id' => $audio->id, 'user_id' => $buyer->id]);

        $this->postJson('/api/mpesa-transactions', $this->kopokopoCallback($buyer, 10))->assertOk();

        $this->assertDatabaseHas('decos', ['user_id' => $buyer->id, 'artist_id' => $artist->id]);
        $this->assertSame(1, Deco::count());
        Notification::assertSentTo($buyer, DecoNotification::class, function ($notification) use ($buyer, $artist) {
            return $notification->artist === $artist->username
                && $notification->toArray($buyer) === [
                    'url' => '/profile/show/'.$artist->username,
                    'from' => $artist->username,
                    'message' => 'Congratulations! '.$artist->username.' decorated you.',
                ];
        });
    }

    public function test_the_old_kopokopo_callback_url_still_works(): void
    {
        Notification::fake();

        $buyer = $this->buyer();
        $video = $this->makeVideo();
        CartVideo::create(['video_id' => $video->id, 'user_id' => $buyer->id]);

        $this->postJson('/api/kopokopo', $this->kopokopoCallback($buyer, 20))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bought_videos', ['video_id' => $video->id, 'user_id' => $buyer->id]);
    }

    public function test_receipt_and_deco_mails_render(): void
    {
        $buyer = $this->buyer();
        $artist = $this->musician();
        $audio = $this->makeAudio($artist);
        $video = $this->makeVideo($artist);

        $audioMail = (new AudioReceiptNotification([$audio]))->toMail($buyer);
        $this->assertSame([['name' => null, 'address' => $buyer->email]], $audioMail->to);
        $audioMail->assertSeeInHtml('You just purchased 1 audio.');
        $audioMail->assertSeeInHtml($audio->name);
        $audioMail->assertSeeInHtml($artist->username);
        $this->assertSame('Audio Receipt', $audioMail->envelope()->subject);

        $videoMail = (new VideoReceiptNotification([$video, $this->makeVideo()]))->toMail($buyer);
        $videoMail->assertSeeInHtml('You just purchased 2 videos.');

        $decoMail = (new DecoNotification($artist->username))->toMail($buyer);
        $decoMail->assertSeeInHtml($artist->username.' decorated you.');
        $this->assertSame('Deco', $decoMail->envelope()->subject);
    }
}
