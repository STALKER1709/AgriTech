<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingPurchase>
 */
class TrainingPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'training_id' => Training::factory(),
            'amount' => fake()->numberBetween(10, 150) * 500,
            'purchased_at' => now(),
        ];
    }
}
