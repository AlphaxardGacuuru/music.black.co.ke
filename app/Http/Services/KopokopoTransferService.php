<?php

namespace App\Http\Services;

use App\Events\KopokopoTransferRecordedEvent;
use App\Http\Resources\KopokopoTransferResource;
use App\Models\KopokopoTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Kopokopo\SDK\K2;
use Illuminate\Support\Facades\Log;

class KopokopoTransferService extends Service
{
    /*
     * Show All Transfers
     */
    public function index()
    {
        $kopokopoTransfers = KopokopoTransfer::with('user')
            ->latest('created_at')
            ->paginate(20);

        return KopokopoTransferResource::collection($kopokopoTransfers);
    }

    /*
     * Store a completed transfer from Kopokopo's send_money callback
     */
    public function store(Request $request)
    {
        // Get Data
        $data = $request->input("data");
        $attributes = $data["attributes"];
        $destination = $attributes["destinations"][0] ?? [];

        $kopokopoTransfer = new KopokopoTransfer;
        $kopokopoTransfer->user_id = $attributes["metadata"]["userId"] ?? null;
        $kopokopoTransfer->kopokopo_id = $data["id"];
        $kopokopoTransfer->kopokopo_created_at = $attributes["created_at"];
        $kopokopoTransfer->amount = $destination["amount"] ?? null;
        $kopokopoTransfer->currency = $attributes["currency"] ?? null;
        $kopokopoTransfer->status = $attributes["status"] ?? null;
        $kopokopoTransfer->errors = $attributes["errors"] ?? null;
        $kopokopoTransfer->transfer_batches = $attributes["transfer_batches"] ?? null;
        $kopokopoTransfer->metadata = $attributes["metadata"] ?? null;
        $saved = $kopokopoTransfer->save();

        if ($saved) {
            KopokopoTransferRecordedEvent::dispatch($kopokopoTransfer);
        }

        return [$saved, "Payment Saved", $kopokopoTransfer];
    }

    /**
     * Send money (M-Pesa mobile wallet) via Kopokopo's SendMoneyService.
     * The recipient's phone number is normalized to Kopokopo's expected
     * "254XXXXXXXXX" format before the request is built.
     */
    public function initiateTransfer(Request $request)
    {
        $amount = $request->input('amount');
        $phoneNumber = $this->normalizePhoneNumber($request->input('destinationReference'));

        $K2 = new K2(MPESATransactionService::config());

        $tokenResponse = $K2->TokenService()->getToken();

        if (($tokenResponse['status'] ?? null) !== 'success') {
            return [
                false,
                $this->kopokopoErrorMessage($tokenResponse, 'Could not authenticate with Kopokopo'),
                $tokenResponse,
            ];
        }

        $accessToken = $tokenResponse['data']['accessToken'];

        $response = $K2->SendMoneyService()->sendMoney([
            'destinations' => [
                [
                    'type' => 'mobile_wallet',
                    'nickname' => $request->input('recipientName'),
                    'phoneNumber' => $phoneNumber,
                    'network' => 'Safaricom',
                    'amount' => $amount,
                    'description' => $request->input('description', 'Black Music payout'),
                ],
            ],
            'currency' => 'KES',
            'metadata' => [
                'userId' => $this->id,
                'notes' => 'Transfer at ' . Carbon::now(),
            ],
            'callbackUrl' => url('/api/kopokopo-transfers'),
            'accessToken' => $accessToken,
        ]);

        if (($response['status'] ?? null) === 'success') {
            return [true, 'Transfer initiated, awaiting confirmation from Kopokopo', $response];
        }

        Log::error('Kopokopo transfer failed', $response);

        return [false, $this->kopokopoErrorMessage($response, 'Kopokopo Transfer Failed'), $response];
    }

    /**
     * Kopokopo's K2 SDK normalizes every failure (a thrown SDK validation
     * exception, a rejected HTTP response, or a token-request failure) into
     * `['status' => 'error', 'data' => ...]`, but `data` itself is shaped
     * differently depending on which of those it was: a plain string for a
     * client-side validation exception, `['errorMessage' => ...]` for a
     * rejected send-money request, or `['errorDescription' => ...]` for a
     * rejected token request. This picks out whichever one is actually
     * present instead of a message that can't say what Kopokopo reported.
     */
    private function kopokopoErrorMessage(array $response, string $fallback): string
    {
        $data = $response['data'] ?? null;

        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            return $data['errorMessage'] ?? $data['errorDescription'] ?? $fallback;
        }

        return $fallback;
    }

    /**
     * The trailing UUID segment of a Kopokopo Location URL, e.g.
     * ".../send_money/{this}", the same id that later shows up as the
     * webhook payload's top-level "data.id".
     */
    public function locationId(string $location): ?string
    {
        if ($location === '') {
            return null;
        }

        $segments = explode('/', rtrim($location, '/'));

        return end($segments) ?: null;
    }
}
