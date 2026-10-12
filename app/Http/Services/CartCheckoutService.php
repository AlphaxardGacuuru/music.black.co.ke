<?php

namespace App\Http\Services;

use App\Events\AudioBoughtEvent;
use App\Events\VideoBoughtEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartCheckoutService extends Service
{
    public function __construct(
        protected BoughtVideoService $boughtVideoService,
        protected BoughtAudioService $boughtAudioService,
    ) {
        parent::__construct();
    }

    /**
     * Buy everything in the user's cart that their wallet balance covers,
     * then tell the buyer and the artists. Runs when an M-Pesa payment lands
     * and when the user checks out with the balance they already have. The
     * user row is locked so two checkouts can't spend the same balance.
     *
     * @return array{videos: mixed, audios: mixed, count: int}
     */
    public function checkout(User $user): array
    {
        [$videos, $audios] = DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            return [
                $this->boughtVideoService->store($user),
                $this->boughtAudioService->store($user),
            ];
        });

        [$structuredBoughtVideos, $boughtVideos, $videoDecoArtists] = $videos;
        [$structuredBoughtAudios, $boughtAudios, $audioDecoArtists] = $audios;

        VideoBoughtEvent::dispatchIf(
            count($boughtVideos) > 0,
            $structuredBoughtVideos,
            $boughtVideos,
            $videoDecoArtists,
            $user
        );

        AudioBoughtEvent::dispatchIf(
            count($boughtAudios) > 0,
            $structuredBoughtAudios,
            $boughtAudios,
            $audioDecoArtists,
            $user
        );

        return [
            'videos' => $structuredBoughtVideos,
            'audios' => $structuredBoughtAudios,
            'count' => count($boughtVideos) + count($boughtAudios),
        ];
    }
}
