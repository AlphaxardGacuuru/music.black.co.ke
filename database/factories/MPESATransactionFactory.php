<?php

namespace Database\Factories;

use App\Models\MPESATransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MPESATransaction>
 */
class MPESATransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'kopokopo_id' => fake()->uuid(),
            'type' => 'incoming_payment',
            'initiation_time' => now()->toIso8601String(),
            'status' => 'Success',
            'event_type' => 'Incoming Payment Request',
            'resource_id' => fake()->uuid(),
            'reference' => strtoupper(fake()->bothify('??#?#??#?#')),
            'origination_time' => now()->toIso8601String(),
            'sender_phone_number' => '+2547' . fake()->numerify('########'),
            'amount' => (string) fake()->randomElement([10, 20, 50, 100]),
            'currency' => 'KES',
            'till_number' => 'K433842',
            'system' => 'Lipa Na M-PESA',
            'resource_status' => 'Received',
            'sender_first_name' => fake()->firstName(),
            'sender_middle_name' => null,
            'sender_last_name' => fake()->lastName(),
        ];
    }
}
