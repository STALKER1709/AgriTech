<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\ProductUnit;
use App\Enums\PublicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use App\Support\Quantity;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates a farmer's product listings.
 *
 * A new listing always starts as a draft. Reaching the catalogue is
 * PublicationService's business, because business rule RG09 decides that, not
 * the farmer.
 */
final class ProductService
{
    /**
     * @param  array{
     *     name: string,
     *     category_id: int,
     *     description: string,
     *     unit_price: int,
     *     unit: string,
     *     stock_quantity: string,
     * }  $attributes
     */
    public function create(User $farmer, array $attributes): Product
    {
        return DB::transaction(fn (): Product => Product::create([
            'farmer_id' => $farmer->id,
            'category_id' => $attributes['category_id'],
            'name' => $attributes['name'],
            'slug' => SlugGenerator::for(Product::class, $attributes['name']),
            'description' => $attributes['description'],
            'unit_price' => Money::fromInteger($attributes['unit_price']),
            'unit' => ProductUnit::from($attributes['unit']),
            'stock_quantity' => Quantity::fromString($attributes['stock_quantity']),
            'status' => PublicationStatus::Draft,
        ]));
    }

    /**
     * @param  array{
     *     name: string,
     *     category_id: int,
     *     description: string,
     *     unit_price: int,
     *     unit: string,
     *     stock_quantity: string,
     * }  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        return DB::transaction(function () use ($product, $attributes): Product {
            // The slug follows the name, but only when the name actually
            // changed: a stable URL is worth more than a tidy one.
            $slug = $product->name === $attributes['name']
                ? $product->slug
                : SlugGenerator::for(Product::class, $attributes['name'], ignoreId: $product->id);

            $product->forceFill([
                'category_id' => $attributes['category_id'],
                'name' => $attributes['name'],
                'slug' => $slug,
                'description' => $attributes['description'],
                'unit_price' => Money::fromInteger($attributes['unit_price'])->amount,
                'unit' => ProductUnit::from($attributes['unit'])->value,
                'stock_quantity' => Quantity::fromString($attributes['stock_quantity'])->toDecimalString(),
            ])->save();

            return $product->refresh();
        });
    }

    /**
     * Archiving replaces deletion: order lines point at products, and an
     * order history that loses its items is not a history.
     */
    public function archive(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->archive();
        });
    }
}
