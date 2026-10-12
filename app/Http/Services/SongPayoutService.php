<?php

namespace App\Http\Services;

use App\Models\SongPayout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongPayoutService extends Service
{
    public function __construct(protected KopokopoTransferService $transfers)
    {
        parent::__construct();
    }

    /**
     * The artist's payout history and what they can withdraw now.
     *
     * @return array{songPayouts: mixed, balance: int}
     */
    public function index(User $artist): array
    {
        $songPayouts = SongPayout::query()
            ->where('user_id', $artist->id)
            ->latest()
            ->get()
            ->map(fn(SongPayout $songPayout) => [
                'id' => $songPayout->id,
                'amount' => $songPayout->amount,
                'fee' => $songPayout->fee,
                'sent' => (int) $songPayout->amount - $songPayout->fee,
                'status' => $songPayout->paid_at ? 'paid' : 'pending',
                'paidAt' => $songPayout->paid_at?->format('d M Y'),
                'created_at' => $songPayout->created_at->format('d M Y'),
            ]);

        return [
            'songPayouts' => $songPayouts,
            'balance' => SongPayout::balanceFor($artist),
        ];
    }

    /**
     * Send the artist's whole balance to their M-Pesa number. The balance is
     * reserved (as a pending payout) before Kopokopo is called, so a second
     * request can't withdraw the same money; it's released again if the
     * transfer can't be started, or if Kopokopo later reports it failed (see
     * KopokopoTransferRecordedListener).
     *
     * @return array{0: bool, 1: string, 2: SongPayout|null}
     */
    public function store(User $artist): array
    {
        if (! $artist->phone) {
            return [false, 'Add your M-Pesa phone number to your account first', null];
        }

        $songPayout = DB::transaction(function () use ($artist) {
            // Serialise payouts per artist.
            User::query()->whereKey($artist->id)->lockForUpdate()->first();

            $pending = SongPayout::query()
                ->where('user_id', $artist->id)
                ->whereNull('paid_at')
                ->exists();

            $balance = SongPayout::balanceFor($artist);

            if ($pending || $balance < (int) $artist->withdrawal || $balance <= SongPayout::feeFor($balance)) {
                return null;
            }

            return SongPayout::query()->create([
                'user_id' => $artist->id,
                'amount' => (string) $balance,
                'fee' => SongPayout::feeFor($balance),
            ]);
        });

        if (! $songPayout) {
            return [false, 'You can withdraw once your balance reaches KES ' . $artist->withdrawal . ' and your last payout has arrived', null];
        }

        $sent = (int) $songPayout->amount - $songPayout->fee;

        [$status, $message, $data] = $this->transfers->initiateTransfer(Request::create('/', 'POST', [
            'destinationReference' => $artist->phone,
            'amount' => $sent,
            'recipientName' => $artist->name,
            'description' => 'Black Music song payout',
        ]));

        if ($status !== true) {
            $songPayout->delete();

            return [false, $message, null];
        }

        $songPayout->update(['kopokopo_reference' => $this->transfers->locationId($data['location'] ?? '')]);

        return [true, 'KES ' . $sent . ' is on its way to ' . $artist->phone, $songPayout];
    }
}
