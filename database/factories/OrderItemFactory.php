<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SubOrder;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = Money::fromInteger(fake()->numberBetween(5, 200) * 100);
        $quantity = Quantity::fromInteger(fake()->numberBetween(1, 20));

        return [
            'sub_order_id' => SubOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price_snapshot' => $unitPrice,
            'line_total' => $unitPrice->multipliedByQuantity($quantity),
        ];
    }

    public function of(Product $product, Quantity $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price_snapshot' => $product->unit_price,
            'line_total' => $product->unit_price->multipliedByQuantity($quantity),
        ]);
    }
}
