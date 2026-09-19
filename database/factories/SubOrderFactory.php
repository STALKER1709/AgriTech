<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubOrderStatus;
use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubOrder>
 */
class SubOrderFactory extends Factory
{
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        self::$sequence++;
        $subtotal = Money::fromInteger(fake()->numberBetween(10, 500) * 100);
        $rate = 5;

        return [
            'order_id' => Order::factory(),
            'farmer_id' => User::factory()->farmer(),
            'reference' => sprintf('CMD-%d-%06d-A', now()->year, self::$sequence),
            'subtotal_amount' => $subtotal,
            'commission_rate_snapshot' => $rate,
            'commission_amount' => $subtotal->percentage($rate),
            'status' => SubOrderStatus::PendingPayment,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubOrderStatus::Paid,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubOrderStatus::Preparing,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubOrderStatus::Delivered,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubOrderStatus::Cancelled,
        ]);
    }

    public function forFarmer(User $farmer): static
    {
        return $this->state(fn (array $attributes): array => [
            'farmer_id' => $farmer->id,
        ]);
    }
}
