<?php

namespace Tests\Feature\Music\Karaoke;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\Karaoke;
use App\Models\User;

trait CreatesKaraokes
{
    /**
     * Create an audio row directly with the models, so these tests do not
     * depend on the audio area's factories.
     */
    protected function createAudio(?User $artist = null): Audio
    {
        $artist ??= User::factory()->create();

        $album = AudioAlbum::create([
            'user_id' => $artist->id,
            'name' => 'Album',
            'released' => now(),
        ]);

        return Audio::create([
            'audio' => 'audios/song.mp3',
            'name' => 'Song Name',
            'user_id' => $artist->id,
            'audio_album_id' => $album->id,
            'genre' => 'Afro',
            'thumbnail' => 'audio-thumbnails/song.png',
            'description' => 'A song',
            'released' => now(),
        ]);
    }

    protected function createKaraoke(array $attributes = []): Karaoke
    {
        return Karaoke::factory()->create([
            'audio_id' => $this->createAudio()->id,
            ...$attributes,
        ]);
    }
}
