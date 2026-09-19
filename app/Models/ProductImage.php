<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property int $position
 */
#[Fillable(['product_id', 'path', 'position'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Where a view should point to display this image.
     *
     * Always a route, never a file URL: the files live on a private disk and
     * the application never relies on storage:link.
     */
    public function url(): string
    {
        return route('catalog.image', ['image' => $this->id]);
    }
}
