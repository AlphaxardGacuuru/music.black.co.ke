<?php

namespace Tests\Feature\Music\Commerce;

use App\Events\MpesaTransactionCreatedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnknownPayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_payment_from_an_unknown_phone_is_acknowledged_without_an_event(): void
    {
        Event::fake([MpesaTransactionCreatedEvent::class]);

        $this->postJson('/api/mpesa-transactions', [
            'data' => [
                'id' => 'abc',
                'type' => 'incoming_payment',
                'attributes' => [
                    'initiation_time' => now()->toIso8601String(),
                    'status' => 'Success',
                    'event' => [
                        'type' => 'Incoming Payment Request',
                        'resource' => [
                            'id' => 'res',
                            'reference' => 'REF123',
                            'origination_time' => now()->toIso8601String(),
                            'sender_phone_number' => '+254799999999',
                            'amount' => '50',
                            'currency' => 'KES',
                            'till_number' => 'K433842',
                            'system' => 'Lipa Na M-PESA',
                            'status' => 'Received',
                            'sender_first_name' => 'Jane',
                            'sender_middle_name' => null,
                            'sender_last_name' => 'Doe',
                        ],
                    ],
                ],
            ],
        ])->assertOk()->assertJsonPath('message', 'User Not Found');

        Event::assertNotDispatched(MpesaTransactionCreatedEvent::class);
    }
}
