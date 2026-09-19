<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Support\Money;
use App\Support\Quantity;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product line of a sub-order.
 *
 * The unit price is a snapshot: the farmer re-pricing the product later must
 * never change what a past order was worth.
 *
 * @property int $id
 * @property int $sub_order_id
 * @property int $product_id
 * @property Quantity $quantity
 * @property Money $unit_price_snapshot
 * @property Money $line_total
 */
#[Fillable(['sub_order_id', 'product_id', 'quantity', 'unit_price_snapshot', 'line_total'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => QuantityCast::class,
            'unit_price_snapshot' => MoneyCast::class,
            'line_total' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<SubOrder, $this> */
    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The line total the stored value has to match.
     */
    public function computeLineTotal(): Money
    {
        return $this->unit_price_snapshot->multipliedByQuantity($this->quantity);
    }
}
