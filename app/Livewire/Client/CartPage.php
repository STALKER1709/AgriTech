<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\Orders\CartService;
use App\Services\Orders\OrderService;
use App\Support\Quantity;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The client's cart: lines grouped by farmer, and the button that turns them
 * into an order.
 */
#[Title('Mon panier')]
class CartPage extends Component
{
    /**
     * The quantity field of each line, keyed by item id.
     *
     * @var array<int, string>
     */
    public array $quantities = [];

    public function mount(CartService $carts): void
    {
        $this->fillQuantities($carts->forClient($this->client()));
    }

    #[Computed]
    public function cart(): Cart
    {
        $cart = app(CartService::class)->forClient($this->client());

        // The farm name is shown next to each group, so the profile is part
        // of what the page needs — lazy loading is disabled in local, and a
        // missing relation here is a 500, not a slow page.
        return $cart->load(['items.product.farmer.farmerProfile', 'items.product.images']);
    }

    public function updateQuantity(int $itemId, CartService $carts): void
    {
        $item = $this->item($itemId);

        try {
            $carts->setQuantity($item, $this->parseQuantity($this->quantities[$itemId] ?? '0'));
        } catch (DomainException|InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
            $this->refreshCart();

            return;
        }

        $this->refreshCart();

        Flux::toast(variant: 'success', text: __('Panier mis à jour.'));
    }

    /**
     * The stepper of the mockup's cart line. One unit at a time; the field
     * between the two buttons still accepts a decimal, because a kilogram is
     * not a sack.
     */
    public function increment(int $itemId, CartService $carts): void
    {
        $this->step($itemId, $carts, 1);
    }

    public function decrement(int $itemId, CartService $carts): void
    {
        $this->step($itemId, $carts, -1);
    }

    private function step(int $itemId, CartService $carts, int $by): void
    {
        $item = $this->item($itemId);
        $next = $item->quantity->plus(Quantity::fromInteger($by));

        if (! $next->isPositive()) {
            $this->remove($itemId, $carts);

            return;
        }

        try {
            $carts->setQuantity($item, $next);
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
            $this->refreshCart();

            return;
        }

        $this->refreshCart();
    }

    public function remove(int $itemId, CartService $carts): void
    {
        $carts->remove($this->item($itemId));

        $this->refreshCart();

        Flux::toast(variant: 'success', text: __('Produit retiré du panier.'));
    }

    public function clear(CartService $carts): void
    {
        $carts->clear($this->cart());

        $this->refreshCart();

        Flux::toast(variant: 'success', text: __('Panier vidé.'));
    }

    /**
     * Place the order, then send the client to it to pay.
     *
     * Nothing is paid here and no stock moves: business rule RG04 waits for a
     * confirmed payment.
     */
    public function placeOrder(OrderService $orders): void
    {
        try {
            $order = $orders->place($this->client(), $this->cart());
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
            $this->refreshCart();

            return;
        }

        $this->redirectRoute('client.orders.show', ['order' => $order->reference], navigate: true);
    }

    /**
     * Read a quantity the way a French keyboard types it.
     *
     * The field shows "12,5", so that is what comes back. The comma is turned
     * into the decimal point Quantity parses, rather than teaching the value
     * object about display conventions.
     */
    private function parseQuantity(string $value): Quantity
    {
        return Quantity::fromString(str_replace(',', '.', trim($value)));
    }

    /**
     * The line the client is acting on, looked up through their own cart so
     * that another client's line cannot be reached by guessing an id.
     */
    private function item(int $itemId): CartItem
    {
        $item = $this->cart()->items()->whereKey($itemId)->first();

        abort_unless($item instanceof CartItem, 404);

        return $item;
    }

    private function refreshCart(): void
    {
        unset($this->cart);

        $this->fillQuantities($this->cart());
    }

    private function fillQuantities(Cart $cart): void
    {
        $this->quantities = $cart->items
            ->mapWithKeys(fn (CartItem $item): array => [$item->id => $item->quantity->format()])
            ->all();
    }

    private function client(): User
    {
        $client = Auth::user();

        abort_unless($client instanceof User, 403);

        return $client;
    }

    public function render(): mixed
    {
        return view('livewire.client.cart-page');
    }
}
