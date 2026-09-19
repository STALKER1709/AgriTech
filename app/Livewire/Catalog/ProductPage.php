<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A single product's public page.
 */
#[Layout('layouts::public')]
class ProductPage extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        // A draft, a product in review, or one whose farmer is suspended is
        // not public. Answering 404 rather than 403 keeps it from confirming
        // that the listing exists at all.
        abort_unless(
            Product::query()->visibleToPublic()->whereKey($product->id)->exists(),
            404,
        );

        $this->product = $product->load(['category', 'images', 'farmer.farmerProfile']);
    }

    public function title(): string
    {
        return $this->product->name;
    }

    public function render(): mixed
    {
        return view('livewire.catalog.product-page')->title($this->product->name);
    }
}
