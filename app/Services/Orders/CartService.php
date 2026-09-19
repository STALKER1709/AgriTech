<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Quantity;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Adds to, changes and empties a client's cart.
 *
 * A cart holds intent, not a reservation: nothing here touches stock. What it
 * does guarantee is that a line never asks for more than the product can
 * currently supply, so the client meets the refusal in the cart rather than
 * at the payment screen.
 */
final class CartService
{
    public function forClient(User $client): Cart
    {
        return Cart::query()->firstOrCreate(['client_id' => $client->id]);
    }

    /**
     * Add a quantity of a product, merging with the line already there.
     */
    public function add(User $client, Product $product, Quantity $quantity): CartItem
    {
        if (! $quantity->isPositive()) {
            throw new DomainException('La quantité doit être supérieure à zéro.');
        }

        if (! $product->isVisibleToPublic()) {
            throw new DomainException('Ce produit n\'est plus proposé à la vente.');
        }

        return DB::transaction(function () use ($client, $product, $quantity): CartItem {
            $cart = $this->forClient($client);

            $item = $cart->items()->where('product_id', $product->id)->first();

            $wanted = $item === null
                ? $quantity
                : $item->quantity->plus($quantity);

            $this->requireStock($product, $wanted);

            if ($item === null) {
                return $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $wanted,
                ]);
            }

            $item->forceFill(['quantity' => $wanted])->save();

            return $item;
        });
    }

    /**
     * Replace a line's quantity outright. Zero removes the line, which is
     * what a client typing 0 into the field means.
     */
    public function setQuantity(CartItem $item, Quantity $quantity): void
    {
        if ($quantity->isNegative()) {
            throw new DomainException('La quantité ne peut pas être négative.');
        }

        if ($quantity->isZero()) {
            $this->remove($item);

            return;
        }

        $this->requireStock($item->product, $quantity);

        $item->forceFill(['quantity' => $quantity])->save();
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->unsetRelation('items');
    }

    private function requireStock(Product $product, Quantity $quantity): void
    {
        if ($product->hasStockFor($quantity)) {
            return;
        }

        throw new DomainException(__('Stock insuffisant : il reste :quantity :unit.', [
            'quantity' => $product->stock_quantity->format(),
            'unit' => $product->unit->shortLabel(),
        ]));
    }
}
