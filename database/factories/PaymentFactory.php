<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->numberBetween(10, 500) * 100,
            'currency' => 'XAF',
            'purpose' => PaymentPurpose::Order,
            'payable_type' => null,
            'payable_id' => null,
            'provider' => 'fake',
            'provider_reference' => 'PAY-'.Str::upper(Str::random(16)),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::Initiated,
            'raw_payload' => null,
            'idempotency_key' => (string) Str::uuid(),
            'confirmed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Expired,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ])->afterCreating(function (Payment $payment): void {
            $payment->markAsRefunded();
        });
    }

    public function purpose(PaymentPurpose $purpose): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => $purpose,
        ]);
    }

    public function for_(Model $payable): static
    {
        return $this->state(fn (array $attributes): array => [
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
        ]);
    }
}
