<?php

namespace Database\Seeders;

use App\Models\CartVideo;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAlbum;
use App\Models\VideoComment;
use App\Models\VideoCommentLike;
use App\Models\VideoLike;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $videos = Video::factory()
            ->count(10)
            ->state(function() {
                $album = VideoAlbum::inRandomOrder()->first();

                return [
                    'video_album_id' => $album->id,
                    'user_id' => $album->user_id,
                ];
            })
            ->create();

        $users = User::all();

        foreach ($videos as $video) {
            // Like by the artist
            VideoLike::factory()->create([
                'video_id' => $video->id,
                'user_id' => $video->user_id,
            ]);

            // Comments, each with a like
            VideoComment::factory()
                ->count(rand(1, 5))
                ->state(fn () => ['user_id' => $users->random()->id])
                ->create(['video_id' => $video->id])
                ->each(fn (VideoComment $comment) => VideoCommentLike::factory()->create([
                    'video_comment_id' => $comment->id,
                    'user_id' => $users->random()->id,
                ]));

            // Every user has the video in their cart
            foreach ($users as $user) {
                CartVideo::create([
                    'video_id' => $video->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
