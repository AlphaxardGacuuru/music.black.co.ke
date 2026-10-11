<?php

namespace App\Http\Services;

use App\Http\Resources\BoughtAudioResource;
use App\Models\BoughtAudio;
use App\Models\BoughtVideo;
use App\Models\CartAudio;
use App\Models\Deco;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BoughtAudioService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getBoughtAudios = BoughtAudio::where('user_id', $this->id)->get();

        return BoughtAudioResource::collection($getBoughtAudios);
    }

    /**
     * Buy every audio in the user's cart that their wallet balance covers.
     *
     * Returns [receipt resource collection, bought Audio models, usernames of
     * the artists that decorated the user].
     */
    public function store(User $user): array
    {
        $structuredBoughtAudios = [];
        $boughtAudios = [];
        $decoArtists = [];

        /* Fetch songs from Cart Audios */
        $cartAudios = CartAudio::where('user_id', $user->id)
            ->get();

        foreach ($cartAudios as $cartAudio) {
            // Check if user can buy songs in cart
            $canBuy = intval(static::balance($user) / BoughtAudio::PRICE);

            if ($canBuy >= 1) {
                $notBought = BoughtAudio::where('user_id', $user->id)
                    ->where('audio_id', $cartAudio->audio_id)
                    ->doesntExist();

                if ($notBought) {
                    // Transaction to make sure an audio is bought and removed from cart and if user qualifies, a deco is saved
                    $artist = DB::transaction(function () use ($cartAudio, $user) {
                        $this->storeBoughtAudio($cartAudio, $user->id);

                        /* Add deco if necessary */
                        $artist = $this->storeDeco($cartAudio, $user->id);

                        /* Delete from cart */
                        CartAudio::where('audio_id', $cartAudio->audio_id)
                            ->where('user_id', $user->id)
                            ->delete();

                        return $artist;
                    });

                    // Update array for use in Event
                    array_push($boughtAudios, $cartAudio->audio);
                    // Array for showing receipt
                    array_push($structuredBoughtAudios, $cartAudio);

                    $artist && array_push($decoArtists, $artist);
                }
            }
        }

        $structuredBoughtAudios = BoughtAudioResource::collection($structuredBoughtAudios);

        return [$structuredBoughtAudios, $boughtAudios, $decoArtists];
    }

    /**
     * Wallet balance: everything paid in via M-Pesa less the cost of every
     * song bought. Queried fresh every time (unlike User::balance(), which
     * reads cached relations) since it changes as the cart is bought.
     */
    public static function balance(User $user): int
    {
        $totalVideos = BoughtVideo::where('user_id', $user->id)->count() * BoughtVideo::PRICE;
        $totalAudios = BoughtAudio::where('user_id', $user->id)->count() * BoughtAudio::PRICE;

        $paid = (int) $user->mpesaTransactions()->sum('amount');

        return $paid - ($totalVideos + $totalAudios);
    }

    // Store Bought Audio
    private function storeBoughtAudio(CartAudio $cartAudio, string $userId): void
    {
        /* Add song to bought_audios */
        $boughtAudio = new BoughtAudio;
        $boughtAudio->audio_id = $cartAudio->audio_id;
        $boughtAudio->price = BoughtAudio::PRICE;
        $boughtAudio->user_id = $userId;
        $boughtAudio->artist_id = $cartAudio->audio->user_id;
        $boughtAudio->save();
    }

    /**
     * Award a deco for every 10 audios bought from the same artist.
     * Returns the artist's username when a deco was awarded.
     */
    public function storeDeco(CartAudio $cartAudio, string $userId): ?string
    {
        $artistId = $cartAudio->audio->user_id;

        /* Check if songs are 10 */
        $userDecos = Deco::where('user_id', $userId)
            ->where('artist_id', $artistId)
            ->count();

        $userAudios = BoughtAudio::where('user_id', $userId)
            ->where('artist_id', $artistId)
            ->count();

        $decoBalance = ($userAudios / 10) - $userDecos;

        $canAddDeco = intval($decoBalance);

        /* If deco balance >= 1 then add deco */
        if ($canAddDeco >= 1) {
            $deco = new Deco;
            $deco->user_id = $userId;
            $deco->artist_id = $artistId;
            $deco->save();

            return $cartAudio->audio->user->username;
        }

        return null;
    }

    /*
     * Artist's Bought Audios */
    public function artistBoughtAudios(string $username): AnonymousResourceCollection
    {
        $artistId = User::where('username', $username)->value('id');

        $getArtistBoughtAudios = BoughtAudio::where('artist_id', $artistId)->get();

        return BoughtAudioResource::collection($getArtistBoughtAudios);
    }
}
