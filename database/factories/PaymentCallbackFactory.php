<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentCallback>
 */
class PaymentCallbackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'event_id' => FakeMobileMoneyGateway::newEventId(),
            'signature' => hash('sha256', (string) fake()->uuid()),
            'payload' => ['status' => 'succeeded'],
            'received_at' => now(),
            'processed_at' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'processed_at' => now(),
        ]);
    }
}
