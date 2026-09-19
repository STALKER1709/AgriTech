<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\SubOrderStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\Money;
use Database\Factories\SubOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The share of an order that belongs to a single farmer.
 *
 * A cart may span several farmers, so this is the unit that actually gets
 * prepared and delivered, and the unit the commission is computed on.
 *
 * @property int $id
 * @property int $order_id
 * @property int $farmer_id
 * @property string $reference
 * @property Money $subtotal_amount
 * @property int $commission_rate_snapshot
 * @property Money $commission_amount
 * @property SubOrderStatus $status
 */
#[Fillable(['order_id', 'farmer_id', 'reference', 'subtotal_amount', 'commission_rate_snapshot', 'commission_amount', 'status'])]
class SubOrder extends Model
{
    /** @use HasFactory<SubOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_amount' => MoneyCast::class,
            'commission_rate_snapshot' => 'integer',
            'commission_amount' => MoneyCast::class,
            'status' => SubOrderStatus::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Derive the sub-order reference from its order: CMD-2026-000123-A.
     */
    public static function referenceFor(Order $order, int $index): string
    {
        return $order->reference.'-'.chr(ord('A') + $index);
    }

    /**
     * Sum the lines. The stored subtotal is what the client actually pays, so
     * it must match this at all times.
     */
    public function recalculateSubtotal(): Money
    {
        return Money::sum(
            $this->items->map(fn (OrderItem $item): Money => $item->line_total),
        );
    }

    /**
     * What the farmer keeps once the platform commission is taken.
     */
    public function farmerPayout(): Money
    {
        return $this->subtotal_amount->minus($this->commission_amount);
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(SubOrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('sub-order', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    public function markAsPaid(): void
    {
        $this->transitionTo(SubOrderStatus::Paid);
    }

    public function markAsPreparing(): void
    {
        $this->transitionTo(SubOrderStatus::Preparing);
    }

    public function markAsDelivered(): void
    {
        $this->transitionTo(SubOrderStatus::Delivered);
    }

    public function cancel(): void
    {
        $this->transitionTo(SubOrderStatus::Cancelled);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForFarmer(Builder $query, User $farmer): void
    {
        $query->where('farmer_id', $farmer->id);
    }
}
