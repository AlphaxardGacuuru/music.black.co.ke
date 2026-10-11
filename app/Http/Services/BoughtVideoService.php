<?php

namespace App\Http\Services;

use App\Http\Resources\BoughtVideoResource;
use App\Models\BoughtVideo;
use App\Models\CartVideo;
use App\Models\Deco;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BoughtVideoService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getBoughtVideos = BoughtVideo::where('user_id', $this->id)->get();

        return BoughtVideoResource::collection($getBoughtVideos);
    }

    /**
     * Buy every video in the user's cart that their wallet balance covers.
     *
     * Returns [receipt resource collection, bought Video models, usernames of
     * the artists that decorated the user].
     */
    public function store(User $user): array
    {
        $structuredBoughtVideos = [];
        $boughtVideos = [];
        $decoArtists = [];

        /* Fetch songs from Cart Videos */
        $cartVideos = CartVideo::where('user_id', $user->id)
            ->get();

        foreach ($cartVideos as $cartVideo) {
            // Check if user can buy songs in cart
            $canBuy = intval(BoughtAudioService::balance($user) / BoughtVideo::PRICE);

            if ($canBuy >= 1) {
                $notBought = BoughtVideo::where('user_id', $user->id)
                    ->where('video_id', $cartVideo->video_id)
                    ->doesntExist();

                if ($notBought) {
                    // Transaction to make sure a video is bought and removed from cart and if user qualifies, a deco is saved
                    $artist = DB::transaction(function () use ($cartVideo, $user) {
                        $this->storeBoughtVideo($cartVideo, $user->id);

                        /* Add deco if necessary */
                        $artist = $this->storeDeco($cartVideo, $user->id);

                        /* Delete from cart */
                        CartVideo::where('video_id', $cartVideo->video_id)
                            ->where('user_id', $user->id)
                            ->delete();

                        return $artist;
                    });

                    // Update array for use in Event
                    array_push($boughtVideos, $cartVideo->video);
                    // Array for showing receipt
                    array_push($structuredBoughtVideos, $cartVideo);

                    // Update Deco array
                    $artist && array_push($decoArtists, $artist);
                }
            }
        }

        $structuredBoughtVideos = BoughtVideoResource::collection($structuredBoughtVideos);

        return [$structuredBoughtVideos, $boughtVideos, $decoArtists];
    }

    // Store Bought Video
    private function storeBoughtVideo(CartVideo $cartVideo, string $userId): void
    {
        /* Add song to bought_videos */
        $boughtVideo = new BoughtVideo;
        $boughtVideo->video_id = $cartVideo->video_id;
        $boughtVideo->price = BoughtVideo::PRICE;
        $boughtVideo->user_id = $userId;
        $boughtVideo->artist_id = $cartVideo->video->user_id;
        $boughtVideo->save();
    }

    /**
     * Award a deco for every 10 videos bought from the same artist.
     * Returns the artist's username when a deco was awarded.
     */
    public function storeDeco(CartVideo $cartVideo, string $userId): ?string
    {
        $artistId = $cartVideo->video->user_id;

        /* Check if songs are 10 */
        $userDecos = Deco::where('user_id', $userId)
            ->where('artist_id', $artistId)
            ->count();

        $userVideos = BoughtVideo::where('user_id', $userId)
            ->where('artist_id', $artistId)
            ->count();

        $decoBalance = ($userVideos / 10) - $userDecos;

        $canAddDeco = intval($decoBalance);

        /* If deco balance >= 1 then add deco */
        if ($canAddDeco >= 1) {
            $deco = new Deco;
            $deco->user_id = $userId;
            $deco->artist_id = $artistId;
            $deco->save();

            return $cartVideo->video->user->username;
        }

        return null;
    }

    /*
     * Artist's Bought Videos */
    public function artistBoughtVideos(string $username): AnonymousResourceCollection
    {
        $artistId = User::where('username', $username)->value('id');

        $getArtistBoughtVideos = BoughtVideo::where('artist_id', $artistId)->get();

        return BoughtVideoResource::collection($getArtistBoughtVideos);
    }
}
