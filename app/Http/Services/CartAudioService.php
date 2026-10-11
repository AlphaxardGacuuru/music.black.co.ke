<?php

namespace App\Http\Services;

use App\Http\Resources\CartAudioResource;
use App\Models\BoughtAudio;
use App\Models\CartAudio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartAudioService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getCartAudios = CartAudio::where('user_id', $this->id)
            ->get();

        return CartAudioResource::collection($getCartAudios);
    }

    /**
     * Add the audio to the cart, or remove it if it is already there.
     */
    public function store(Request $request): array
    {
        $cartAudio = 'data';

        $userId = auth('sanctum')->id();

        /* Check if item is already in cart */
        $inCart = CartAudio::where('audio_id', $request->input('audio'))
            ->where('user_id', $userId)
            ->exists();

        /* Check item in not already bought */
        $notBought = BoughtAudio::where('user_id', $userId)
            ->where('audio_id', $request->input('audio'))
            ->doesntExist();

        /* Insert or Remove from cart */
        if ($inCart && $notBought) {
            CartAudio::where('audio_id', $request->input('audio'))
                ->where('user_id', $userId)
                ->delete();

            $message = 'Audio removed from Cart';
            $added = false;
        } else {
            // OLD inserted a second row when the audio was in the cart and
            // already bought. cart_audios is now unique per user, so reuse it.
            $cartAudio = CartAudio::firstOrCreate([
                'audio_id' => $request->input('audio'),
                'user_id' => $userId,
            ]);

            $message = 'Audio added to Cart';
            $added = true;
        }

        return [$added, $message, $cartAudio];
    }
}
