<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\QuantityCast;
use App\Support\Money;
use App\Support\Quantity;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product line of a cart.
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property Quantity $quantity
 */
#[Fillable(['cart_id', 'product_id', 'quantity'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => QuantityCast::class,
        ];
    }

    /** @return BelongsTo<Cart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Today's price for this line. The order snapshots its own copy, because
     * a price the farmer changes tomorrow must not rewrite a past order.
     */
    public function lineTotal(): Money
    {
        return $this->product->unit_price->multipliedByQuantity($this->quantity);
    }

    /**
     * Whether this line could be ordered right now.
     *
     * A display-time reading, deliberately not the last word: the order is
     * placed against a locked re-read of the same two conditions.
     */
    public function isOrderable(): bool
    {
        return $this->product->isVisibleToPublic()
            && $this->quantity->isPositive()
            && $this->product->hasStockFor($this->quantity);
    }

    /**
     * Why the line cannot be ordered, in the words the client sees.
     */
    public function unavailableReason(): ?string
    {
        if (! $this->product->isVisibleToPublic()) {
            return __('Ce produit n\'est plus proposé à la vente.');
        }

        if (! $this->product->hasStockFor($this->quantity)) {
            return __('Stock insuffisant : il reste :quantity :unit.', [
                'quantity' => $this->product->stock_quantity->format(),
                'unit' => $this->product->unit->shortLabel(),
            ]);
        }

        return null;
    }
}
