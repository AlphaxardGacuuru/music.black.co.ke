<?php

namespace App\Listeners;

use App\Events\MpesaTransactionCreatedEvent;
use App\Http\Services\CartCheckoutService;
use App\Models\User;

class BuyCartListener
{
    public function __construct(protected CartCheckoutService $checkout)
    {
        //
    }

    /**
     * A payment just landed in the user's wallet: buy what's in their cart.
     */
    public function handle(MpesaTransactionCreatedEvent $event): void
    {
        $user = User::query()->find($event->mpesaTransaction->user_id);

        if ($user) {
            $this->checkout->checkout($user);
        }
    }
}
