<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int $client_id
 * @property Money $total_amount
 * @property OrderStatus $status
 * @property Carbon|null $expires_at
 */
#[Fillable(['reference', 'client_id', 'total_amount', 'status', 'expires_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => MoneyCast::class,
            'status' => OrderStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** @return HasMany<SubOrder, $this> */
    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    /** @return HasManyThrough<OrderItem, SubOrder, $this> */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, SubOrder::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Build the next human-readable reference: CMD-2026-000123.
     *
     * The sequence restarts every year, which keeps references short enough to
     * read out over the phone.
     */
    public static function nextReference(): string
    {
        $year = now()->year;
        $prefix = sprintf('CMD-%d-', $year);

        $lastNumber = static::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Recompute the total from the sub-orders, which hold the real lines.
     */
    public function recalculateTotal(): Money
    {
        return Money::sum(
            $this->subOrders->map(fn (SubOrder $subOrder): Money => $subOrder->subtotal_amount),
        );
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(OrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('order', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    /**
     * Business rule RG06: only a server-verified payment gets an order here.
     */
    public function markAsPaid(): void
    {
        $this->transitionTo(OrderStatus::Paid);
    }

    public function markAsPreparing(): void
    {
        $this->transitionTo(OrderStatus::Preparing);
    }

    public function markAsDelivered(): void
    {
        $this->transitionTo(OrderStatus::Delivered);
    }

    public function cancel(): void
    {
        $this->transitionTo(OrderStatus::Cancelled);
    }

    /** @param  Builder<$this>  $query */
    public function scopeAwaitingPayment(Builder $query): void
    {
        $query->where('status', OrderStatus::PendingPayment);
    }

    /** @param  Builder<$this>  $query */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', OrderStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
