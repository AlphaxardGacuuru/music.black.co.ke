<?php

namespace App\Listeners;

use App\Events\AudioBoughtEvent;
use App\Events\MpesaTransactionCreatedEvent;
use App\Events\VideoBoughtEvent;
use App\Http\Services\BoughtAudioService;
use App\Http\Services\BoughtVideoService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Once an M-Pesa payment lands, buy everything in the payer's cart that their
 * wallet balance covers (OLD KopokopoController::store did this inline).
 *
 * Idempotent: purchases are driven by the wallet balance and skip songs
 * already bought, so a repeated callback for the same payment buys nothing
 * new. The payer's row is locked for the duration so two callbacks arriving
 * together cannot both spend the same balance.
 */
class BuyCartListener
{
    public function __construct(
        protected BoughtVideoService $boughtVideoService,
        protected BoughtAudioService $boughtAudioService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MpesaTransactionCreatedEvent $event): void
    {
        $userId = $event->mpesaTransaction->user_id;

        if (! $userId) {
            return;
        }

        [$videos, $audios, $user] = DB::transaction(function () use ($userId) {
            $user = User::whereKey($userId)->lockForUpdate()->first();

            if (! $user) {
                return [null, null, null];
            }

            //  Buy Videos
            $videos = $this->boughtVideoService->store($user);

            //  Buy Audios
            $audios = $this->boughtAudioService->store($user);

            return [$videos, $audios, $user];
        });

        if (! $user) {
            return;
        }

        [$structuredBoughtVideos, $boughtVideos, $videoDecoArtists] = $videos;

        // Check if Videos were actually bought
        VideoBoughtEvent::dispatchIf(
            count($boughtVideos) > 0,
            $structuredBoughtVideos,
            $boughtVideos,
            $videoDecoArtists,
            $user
        );

        [$structuredBoughtAudios, $boughtAudios, $audioDecoArtists] = $audios;

        // Check if Audios were actually bought
        AudioBoughtEvent::dispatchIf(
            count($boughtAudios) > 0,
            $structuredBoughtAudios,
            $boughtAudios,
            $audioDecoArtists,
            $user
        );
    }
}
