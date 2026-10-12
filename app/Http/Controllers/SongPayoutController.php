<?php

namespace App\Http\Controllers;

use App\Http\Services\SongPayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SongPayoutController extends Controller
{
    public function __construct(protected SongPayoutService $service)
    {
        //
    }

    /**
     * The signed-in artist's payouts and withdrawable balance.
     */
    public function index(Request $request): Response
    {
        return response(['data' => $this->service->index($request->user())], 200);
    }

    /**
     * Withdraw the signed-in artist's balance to M-Pesa. The amount is always
     * the full balance, worked out on the server.
     */
    public function store(Request $request): Response
    {
        [$started, $message, $songPayout] = $this->service->store($request->user());

        return response([
            'message' => $message,
            'data' => $songPayout,
        ], $started ? 200 : 422);
    }
}
