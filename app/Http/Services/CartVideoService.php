<?php

namespace App\Http\Services;

use App\Http\Resources\CartVideoResource;
use App\Models\BoughtVideo;
use App\Models\CartVideo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartVideoService extends Service
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $getCartVideos = CartVideo::where('user_id', $this->id)
            ->get();

        return CartVideoResource::collection($getCartVideos);
    }

    /**
     * Add the video to the cart, or remove it if it is already there.
     */
    public function store(Request $request): array
    {
        $cartVideo = 'data';

        $userId = auth('sanctum')->id();

        /* Check if item is already in cart */
        $inCart = CartVideo::where('video_id', $request->input('video'))
            ->where('user_id', $userId)
            ->exists();

        /* Check item in not already bought */
        $notBought = BoughtVideo::where('user_id', $userId)
            ->where('video_id', $request->input('video'))
            ->doesntExist();

        /* Insert or Remove from cart */
        if ($inCart && $notBought) {
            CartVideo::where('video_id', $request->input('video'))
                ->where('user_id', $userId)
                ->delete();

            $message = 'Video removed from Cart';
            $added = false;
        } else {
            // OLD inserted a second row when the video was in the cart and
            // already bought. cart_videos is now unique per user, so reuse it.
            $cartVideo = CartVideo::firstOrCreate([
                'video_id' => $request->input('video'),
                'user_id' => $userId,
            ]);

            $message = 'Video added to Cart';
            $added = true;
        }

        return [$added, $message, $cartVideo];
    }
}
