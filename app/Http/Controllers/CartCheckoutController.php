<?php

namespace App\Http\Controllers;

use App\Http\Services\CartCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartCheckoutController extends Controller
{
    public function __construct(protected CartCheckoutService $service)
    {
        //
    }

    /**
     * Buy the cart with the balance already in the user's wallet.
     */
    public function store(Request $request): Response
    {
        $result = $this->service->checkout($request->user());

        if ($result['count'] === 0) {
            return response([
                'message' => 'Your balance doesn\'t cover anything in your cart yet. Top up with M-Pesa to buy.',
            ], 422);
        }

        return response([
            'message' => $result['count'] . ' ' . ($result['count'] === 1 ? 'song' : 'songs') . ' bought',
            'data' => [
                'videos' => $result['videos'],
                'audios' => $result['audios'],
            ],
        ], 200);
    }
}
