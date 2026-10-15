<?php

namespace App\Listeners;

use App\Events\KopokopoTransferRecordedEvent;
use App\Models\SongPayout;
use App\Notifications\SongPayoutNotification;

/**
 * A song payout is only marked paid once Kopokopo's webhook confirms the
 * transfer actually completed, not when the disbursement request was
 * merely accepted (kopokopo_reference is set instead of paid_at at
 * initiation time). A confirmed failure deletes the pending payout so it
 * can be retried. The artist's "KES X has been sent" notification is
 * deferred here too, for the same reason: it shouldn't go out before the
 * transfer is actually confirmed.
 */
class KopokopoTransferRecordedListener
{
    public function handle(KopokopoTransferRecordedEvent $event): void
    {
        $transfer = $event->kopokopoTransfer;
        $reference = $transfer->kopokopo_id;

        if (! $reference) {
            return;
        }

        if ($transfer->isConfirmedSuccess()) {
            $this->confirmSongPayouts($reference);

            return;
        }

        if ($transfer->isConfirmedFailure()) {
            // Deleting the pending payout returns the money to the artist's
            // balance so they can withdraw it again.
            SongPayout::query()
                ->where('kopokopo_reference', $reference)
                ->whereNull('paid_at')
                ->delete();
        }
    }

    private function confirmSongPayouts(string $reference): void
    {
        SongPayout::query()
            ->with('user')
            ->where('kopokopo_reference', $reference)
            ->whereNull('paid_at')
            ->get()
            ->each(function (SongPayout $songPayout) {
                $songPayout->update(['paid_at' => now()]);

                $songPayout->user?->notify(new SongPayoutNotification($songPayout));
            });
    }
}
