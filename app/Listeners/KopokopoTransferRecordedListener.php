<?php

namespace App\Listeners;

use App\Events\KopokopoTransferInitiated;
use App\Events\KopokopoTransferRecordedEvent;
use App\Http\Services\Service;
use App\Models\Referral;
use App\Models\SongPayout;
use App\Notifications\SongPayoutNotification;
use Illuminate\Support\Collection;

/**
 * A referrer's payout is only marked paid once Kopokopo's webhook
 * confirms the transfer actually completed, not when the disbursement
 * request was merely accepted (see
 * KopokopoTransferService::payReferrer(), which sets kopokopo_reference
 * instead of paid_at at initiation time).
 * A confirmed failure clears that reference so the payout can be retried.
 * The recipient's "KES X has been sent" notification is deferred here too,
 * for the same reason: it shouldn't go out before the transfer is actually
 * confirmed.
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
            $this->confirmReferrals($reference);
            $this->confirmSongPayouts($reference);

            return;
        }

        if ($transfer->isConfirmedFailure()) {
            Referral::query()
                ->where('kopokopo_reference', $reference)
                ->whereNull('paid_at')
                ->update(['kopokopo_reference' => null, 'amount_paid' => null]);

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

    private function confirmReferrals(string $reference): void
    {
        /** @var Collection<int, Referral> $referrals */
        $referrals = Referral::query()
            ->with('referrer')
            ->where('kopokopo_reference', $reference)
            ->whereNull('paid_at')
            ->get();

        if ($referrals->isEmpty()) {
            return;
        }

        $referrals->each(fn(Referral $referral) => $referral->update(['paid_at' => now()]));

        $referrer = $referrals->first()->referrer;

        if ($referrer?->phone) {
            KopokopoTransferInitiated::dispatch(
                Service::normalizePhoneNumber($referrer->phone),
                (float) $referrals->sum('amount_paid'),
                $referrer->name,
                'Black Music referral reward',
            );
        }
    }
}
