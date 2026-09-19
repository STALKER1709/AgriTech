<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\QuantityCast;
use App\Enums\ProductUnit;
use App\Enums\PublicationStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\Money;
use App\Support\Quantity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $farmer_id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property Money $unit_price
 * @property ProductUnit $unit
 * @property Quantity $stock_quantity
 * @property PublicationStatus $status
 */
#[Fillable(['farmer_id', 'category_id', 'name', 'slug', 'description', 'unit_price', 'unit', 'stock_quantity', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'unit' => ProductUnit::class,
            'stock_quantity' => QuantityCast::class,
            'status' => PublicationStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the requested quantity is available.
     *
     * Business rule RG03 checks this before an order is created; RG04 checks
     * it again under a row lock before the stock is actually moved.
     */
    public function hasStockFor(Quantity $quantity): bool
    {
        return $this->stock_quantity->isGreaterThanOrEqualTo($quantity);
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity->isPositive();
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(PublicationStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('product', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    public function submitForReview(): void
    {
        $this->transitionTo(PublicationStatus::InReview);
    }

    public function publish(): void
    {
        $this->transitionTo(PublicationStatus::Published);
    }

    public function rejectPublication(): void
    {
        $this->transitionTo(PublicationStatus::Rejected);
    }

    public function archive(): void
    {
        $this->transitionTo(PublicationStatus::Archived);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForFarmer(Builder $query, User $farmer): void
    {
        $query->where('farmer_id', $farmer->id);
    }
}
