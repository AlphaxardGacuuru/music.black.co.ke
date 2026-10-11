<?php

namespace Database\Seeders;

use App\Models\Audio;
use App\Models\AudioAlbum;
use App\Models\AudioComment;
use App\Models\AudioCommentLike;
use App\Models\AudioLike;
use App\Models\CartAudio;
use App\Models\User;
use Illuminate\Database\Seeder;

class AudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Needs users and audio albums (AudioAlbumSeeder) to exist already.
     * Mirrors the OLD AudioFactory/AudioCommentFactory afterCreating hooks:
     * each audio gets a like from its artist, 1 to 5 comments (each with a
     * like) and sits in every user's cart.
     */
    public function run(): void
    {
        $users = User::all();
        $albums = AudioAlbum::all();

        for ($i = 0; $i < 10; $i++) {
            $album = $albums->random();

            $audio = Audio::factory()->create([
                'audio_album_id' => $album->id,
                'user_id' => $album->user_id,
            ]);

            AudioLike::factory()->create([
                'audio_id' => $audio->id,
                'user_id' => $audio->user_id,
            ]);

            AudioComment::factory()
                ->count(rand(1, 5))
                ->state(fn () => ['user_id' => $users->random()->id])
                ->create(['audio_id' => $audio->id])
                ->each(fn (AudioComment $comment) => AudioCommentLike::factory()->create([
                    'audio_comment_id' => $comment->id,
                    'user_id' => $users->random()->id,
                ]));

            foreach ($users as $user) {
                CartAudio::create([
                    'audio_id' => $audio->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
