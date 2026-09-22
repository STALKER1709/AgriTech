<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Orders\CartService;
use App\Support\Money;
use App\Support\Quantity;
use DomainException;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A single product's public page.
 */
#[Layout('layouts::public')]
class ProductPage extends Component
{
    public Product $product;

    public string $quantity = '1';

    /**
     * The image shown large. The mockup's gallery switches the main picture
     * when a thumbnail is tapped.
     */
    public ?int $currentImageId = null;

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
        $this->currentImageId = $this->product->images->first()?->id;
    }

    #[Computed]
    public function currentImage(): ?ProductImage
    {
        return $this->product->images->firstWhere('id', $this->currentImageId)
            ?? $this->product->images->first();
    }

    public function showImage(int $imageId): void
    {
        // Looked up in this product's own images, so another product's file
        // cannot be shown by passing its id.
        abort_unless($this->product->images->contains('id', $imageId), 404);

        $this->currentImageId = $imageId;
    }

    /**
     * The quantity currently asked for, or one unit when the field holds
     * something that is not a quantity at all.
     */
    public function askedQuantity(): Quantity
    {
        try {
            return Quantity::fromString(str_replace(',', '.', trim($this->quantity)));
        } catch (InvalidArgumentException) {
            return Quantity::fromInteger(1);
        }
    }

    public function increment(): void
    {
        $next = $this->askedQuantity()->plus(Quantity::fromInteger(1));

        if (! $this->product->hasStockFor($next)) {
            Flux::toast(variant: 'warning', text: __('Il ne reste que :quantity :unit.', [
                'quantity' => $this->product->stock_quantity->format(),
                'unit' => $this->product->unit->countLabel($this->product->stock_quantity),
            ]));

            return;
        }

        $this->quantity = $next->format();
    }

    public function decrement(): void
    {
        $next = $this->askedQuantity()->minus(Quantity::fromInteger(1));

        $this->quantity = $next->isPositive() ? $next->format() : '1';
    }

    /**
     * What the sticky bar announces. An estimate, and named as such: the
     * order is priced again, server-side, when it is actually placed.
     */
    public function estimatedTotal(): Money
    {
        return $this->product->unit_price->multipliedByQuantity($this->askedQuantity());
    }

    /**
     * @return Collection<int, Product>
     */
    public function alsoFromFarmer(): Collection
    {
        return Product::query()
            ->visibleToPublic()
            ->with(['images', 'category', 'farmer.farmerProfile'])
            ->where('farmer_id', $this->product->farmer_id)
            ->whereKeyNot($this->product->id)
            ->latest()
            ->limit(2)
            ->get();
    }

    /**
     * Whether the person reading can actually buy: browsing is open to all,
     * buying is a client's.
     */
    public function canAddToCart(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isClient() && $user->isActive();
    }

    public function isVisitor(): bool
    {
        return ! Auth::check();
    }

    public function addToCart(CartService $carts): void
    {
        $user = Auth::user();

        // A visitor is sent to log in and comes straight back here.
        if (! $user instanceof User) {
            session(['url.intended' => route('catalog.product', ['product' => $this->product->slug])]);

            $this->redirectRoute('login', navigate: true);

            return;
        }

        abort_unless($user->isClient() && $user->isActive(), 403);

        try {
            $carts->add(
                $user,
                $this->product,
                Quantity::fromString(str_replace(',', '.', trim($this->quantity))),
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Produit ajouté au panier.'));
    }

    /**
     * Open (or find) the thread with the farmer behind this product.
     */
    public function contactFarmer(MessagingService $messaging): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            session(['url.intended' => route('catalog.product', ['product' => $this->product->slug])]);

            $this->redirectRoute('login', navigate: true);

            return;
        }

        abort_unless($user->isClient() && $user->isActive(), 403);

        $conversation = $messaging->conversationAboutProduct($this->product, $user);

        $this->redirectRoute('client.messages.show', ['conversation' => $conversation->id], navigate: true);
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
