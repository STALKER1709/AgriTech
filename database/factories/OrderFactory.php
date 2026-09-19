<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$sequence++;

        return [
            'reference' => sprintf('CMD-%d-%06d', now()->year, self::$sequence),
            'client_id' => User::factory()->client(),
            'total_amount' => fake()->numberBetween(10, 500) * 100,
            'status' => OrderStatus::PendingPayment,
            'expires_at' => now()->addMinutes(30),
        ];
    }

    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::PendingPayment,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * An unpaid order whose window has closed, ready for the cancellation
     * sweep to pick up.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::PendingPayment,
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'expires_at' => null,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Preparing,
            'expires_at' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Delivered,
            'expires_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Cancelled,
            'expires_at' => null,
        ]);
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_id' => $client->id,
        ]);
    }
}
