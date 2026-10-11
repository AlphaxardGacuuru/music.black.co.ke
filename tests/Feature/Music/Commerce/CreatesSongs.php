<?php

namespace Tests\Feature\Music\Commerce;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\MPESATransaction;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAlbum;

/**
 * Builds songs and payments straight from the models so the commerce tests
 * do not depend on the audio/video areas' factories.
 */
trait CreatesSongs
{
    protected function musician(array $attributes = []): User
    {
        return User::factory()->create(['account_type' => 'musician'] + $attributes);
    }

    protected function makeAudio(?User $artist = null, array $attributes = []): Audio
    {
        $artist ??= $this->musician();

        $album = AudioAlbum::create([
            'user_id' => $artist->id,
            'name' => 'Singles',
            'released' => now(),
        ]);

        return Audio::create([
            'audio' => 'audios/song.mp3',
            'name' => fake()->words(2, true),
            'user_id' => $artist->id,
            'audio_album_id' => $album->id,
            'genre' => 'Afro',
            'thumbnail' => 'audio-thumbnails/song.jpg',
            'description' => 'A song',
            'released' => now(),
        ] + $attributes);
    }

    protected function makeVideo(?User $artist = null, array $attributes = []): Video
    {
        $artist ??= $this->musician();

        $album = VideoAlbum::create([
            'user_id' => $artist->id,
            'name' => 'Singles',
            'released' => now(),
        ]);

        return Video::create([
            'video' => 'videos/song.mp4',
            'name' => fake()->words(2, true),
            'user_id' => $artist->id,
            'video_album_id' => $album->id,
            'genre' => 'Afro',
            'thumbnail' => 'video-thumbnails/song.jpg',
            'description' => 'A song',
            'released' => now(),
        ] + $attributes);
    }

    /**
     * Record a payment directly (no event).
     */
    protected function pay(User $user, int $amount): MPESATransaction
    {
        $mpesaTransaction = new MPESATransaction;
        $mpesaTransaction->kopokopo_id = fake()->uuid();
        $mpesaTransaction->reference = strtoupper(fake()->bothify('??########'));
        $mpesaTransaction->amount = $amount;
        $mpesaTransaction->user_id = $user->id;
        $mpesaTransaction->save();

        return $mpesaTransaction;
    }

    /**
     * Kopokopo's STK push callback payload.
     */
    protected function kopokopoCallback(User $user, int $amount, string $reference = 'QKX123ABC'): array
    {
        return [
            'data' => [
                'id' => fake()->uuid(),
                'type' => 'incoming_payment',
                'attributes' => [
                    'initiation_time' => now()->toIso8601String(),
                    'status' => 'Success',
                    'event' => [
                        'type' => 'Incoming Payment Request',
                        'resource' => [
                            'id' => fake()->uuid(),
                            'reference' => $reference,
                            'origination_time' => now()->toIso8601String(),
                            'sender_phone_number' => substr_replace($user->phone, '+254', 0, -9),
                            'amount' => (string) $amount,
                            'currency' => 'KES',
                            'till_number' => 'K000000',
                            'system' => 'Lipa Na M-PESA',
                            'status' => 'Received',
                            'sender_first_name' => 'John',
                            'sender_middle_name' => null,
                            'sender_last_name' => 'Doe',
                        ],
                    ],
                ],
            ],
        ];
    }
}
