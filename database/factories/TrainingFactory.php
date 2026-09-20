<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\TrainingFormat;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'farmer_id' => User::factory()->farmer(),
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(10, 150) * 500,
            'format' => fake()->randomElement(TrainingFormat::cases()),
            'included_in_subscription' => false,
            'status' => PublicationStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Draft,
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::InReview,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Rejected,
            'rejection_reason' => 'Contenu insuffisant pour une formation complète.',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Published,
        ]);
    }

    public function includedInSubscription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'included_in_subscription' => true,
        ]);
    }

    public function forFarmer(User $farmer): static
    {
        return $this->state(fn (array $attributes): array => [
            'farmer_id' => $farmer->id,
        ]);
    }
}
