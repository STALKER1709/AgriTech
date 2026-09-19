<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A client's cart, which may span several farmers.
 *
 * Nothing here is authoritative: the prices shown are today's prices, and the
 * availability shown is what was true when the page was rendered. Both are
 * read again, under a row lock, when the order is actually placed.
 *
 * @property int $id
 * @property int $client_id
 */
#[Fillable(['client_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * The lines, grouped the way the cart is displayed and the way the order
     * will be split: one group per farmer.
     *
     * @return Collection<int|string, Collection<int, CartItem>>
     */
    public function itemsByFarmer(): Collection
    {
        // toBase() first: grouping an Eloquent collection would hand back
        // collections of collections still typed as model collections, which
        // is a shape Eloquent's generics cannot describe.
        return $this->items
            ->toBase()
            ->groupBy(fn (CartItem $item): int => $item->product->farmer_id);
    }

    /**
     * What the client would pay, counting only the lines that can actually be
     * ordered. A line whose product went out of stock is shown, and excluded.
     */
    public function total(): Money
    {
        return Money::sum(
            $this->items
                ->filter(fn (CartItem $item): bool => $item->isOrderable())
                ->map(fn (CartItem $item): Money => $item->lineTotal()),
        );
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Whether every line can be ordered as it stands. A cart with a single
     * unavailable line cannot be checked out: business rule RG03 is checked
     * per line, and an order is all or nothing.
     */
    public function isOrderable(): bool
    {
        return ! $this->isEmpty()
            && $this->items->every(fn (CartItem $item): bool => $item->isOrderable());
    }

    public function itemCount(): int
    {
        return $this->items->count();
    }
}
