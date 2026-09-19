<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'plan_id' => SubscriptionPlan::factory(),
            'starts_at' => null,
            'ends_at' => null,
            'status' => SubscriptionStatus::PendingPayment,
        ];
    }

    public function active(int $remainingDays = 30): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addDays($remainingDays),
        ]);
    }

    /**
     * Still flagged active but past its term: what the expiry sweep looks for.
     */
    public function lapsed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Cancelled,
        ]);
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_id' => $client->id,
        ]);
    }
}
