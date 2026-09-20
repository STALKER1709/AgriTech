<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Orders\CartService;
use App\Support\Quantity;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
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
