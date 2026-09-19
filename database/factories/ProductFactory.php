<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductUnit;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word().' '.fake()->word();

        return [
            'farmer_id' => User::factory()->farmer(),
            'category_id' => Category::factory(),
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            // Prices land on round hundreds, as they do on a market stall.
            'unit_price' => fake()->numberBetween(5, 500) * 100,
            'unit' => fake()->randomElement(ProductUnit::cases()),
            'stock_quantity' => Quantity::fromInteger(fake()->numberBetween(10, 500)),
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

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Published,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Rejected,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublicationStatus::Archived,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => Quantity::zero(),
        ]);
    }

    public function withStock(Quantity $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => $quantity,
        ]);
    }

    public function forFarmer(User $farmer): static
    {
        return $this->state(fn (array $attributes): array => [
            'farmer_id' => $farmer->id,
        ]);
    }
}
