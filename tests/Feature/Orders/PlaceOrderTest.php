<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\SubOrderStatus;
use App\Livewire\Client\CartPage;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Orders\CartService;
use App\Services\Orders\OrderService;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

// The commission rate and the cancellation delay are platform settings; an
// order cannot be priced without them.
beforeEach(fn () => test()->seed(SettingSeeder::class));

/**
 * Business rule RG03 — an order is only created for what is available — and
 * what an order freezes at the moment it is placed.
 */
function cartOf(User $client, array $lines): void
{
    $carts = app(CartService::class);

    foreach ($lines as [$product, $quantity]) {
        $carts->add($client, $product, $quantity);
    }
}

describe('création', function () {
    it('crée une sous-commande par agriculteur', function () {
        $client = User::factory()->client()->create();
        $first = productOnSale(['unit_price' => 1_000, 'name' => 'Tomates']);
        $second = productOnSale(['unit_price' => 2_500, 'name' => 'Miel']);

        cartOf($client, [[$first, Quantity::fromInteger(2)], [$second, Quantity::fromInteger(1)]]);

        $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        expect($order->subOrders)->toHaveCount(2)
            ->and($order->status)->toBe(OrderStatus::PendingPayment)
            ->and($order->total_amount->amount)->toBe(4_500)
            ->and($order->total_amount->amount)->toBeMoney();

        expect($order->subOrders->pluck('reference')->all())
            ->toBe([$order->reference.'-A', $order->reference.'-B']);
    });

    it('fige le prix unitaire du jour', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['unit_price' => 1_000]);

        cartOf($client, [[$product, Quantity::fromInteger(3)]]);

        $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        $product->forceFill(['unit_price' => 9_999])->save();

        $item = $order->subOrders->first()->items->first();

        expect($item->unit_price_snapshot->amount)->toBe(1_000)
            ->and($item->line_total->amount)->toBe(3_000);
    });

    it('fige le taux de commission de la plateforme', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['unit_price' => 10_000]);

        cartOf($client, [[$product, Quantity::fromInteger(1)]]);

        $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));
        $subOrder = $order->subOrders->first();

        expect($subOrder->commission_rate_snapshot)->toBe(5)
            ->and($subOrder->commission_amount->amount)->toBe(500)
            ->and($subOrder->farmerPayout()->amount)->toBe(9_500);

        // Changing the platform rate afterwards must not rewrite accounting
        // already agreed with the farmer.
        Setting::query()
            ->where('key', Setting::PLATFORM_COMMISSION_RATE)
            ->update(['value' => '20']);

        expect($subOrder->refresh()->commission_rate_snapshot)->toBe(5)
            ->and($subOrder->commission_amount->amount)->toBe(500);
    });

    it('calcule une ligne fractionnaire sans flottant', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['unit_price' => 1_000]);

        cartOf($client, [[$product, Quantity::fromString('12.5')]]);

        $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        expect($order->total_amount->amount)->toBe(12_500)
            ->and($order->total_amount->amount)->toBeMoney();
    });

    it('vide le panier une fois la commande créée', function () {
        $client = User::factory()->client()->create();
        cartOf($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        expect(app(CartService::class)->forClient($client)->items()->count())->toBe(0);
    });

    it('pose une échéance de paiement issue des paramètres', function () {
        $client = User::factory()->client()->create();
        cartOf($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        expect($order->expires_at?->diffInMinutes(now(), absolute: true))->toBeLessThan(31);
    });

    it('ne touche pas au stock', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(20)]);

        cartOf($client, [[$product, Quantity::fromInteger(5)]]);
        app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        // Business rule RG04: the stock waits for a confirmed payment.
        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('20.000');
    });

    it('numérote les commandes dans une séquence annuelle', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        cartOf($client, [[$product, Quantity::fromInteger(1)]]);
        $first = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        cartOf($client, [[$product, Quantity::fromInteger(1)]]);
        $second = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

        expect($first->reference)->toBe(sprintf('CMD-%d-000001', now()->year))
            ->and($second->reference)->toBe(sprintf('CMD-%d-000002', now()->year));
    });
});

describe('RG03', function () {
    it('refuse une commande dont la quantité dépasse le stock', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(10)]);

        cartOf($client, [[$product, Quantity::fromInteger(10)]]);

        // The stock drops after the cart was filled: the order must be
        // refused, not created and sorted out later.
        $product->forceFill(['stock_quantity' => Quantity::fromInteger(4)])->save();

        expect(fn () => app(OrderService::class)->place($client, app(CartService::class)->forClient($client)))
            ->toThrow(DomainException::class);

        expect(Order::query()->count())->toBe(0)
            ->and(SubOrder::query()->count())->toBe(0);
    });

    it('refuse une commande dont un produit a été dépublié', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        cartOf($client, [[$product, Quantity::fromInteger(1)]]);

        $product->archive();

        expect(fn () => app(OrderService::class)->place($client, app(CartService::class)->forClient($client)))
            ->toThrow(DomainException::class);

        expect(Order::query()->count())->toBe(0);
    });

    it('refuse un panier vide', function () {
        $client = User::factory()->client()->create();

        expect(fn () => app(OrderService::class)->place($client, app(CartService::class)->forClient($client)))
            ->toThrow(DomainException::class);
    });

    it('ne laisse rien derrière elle quand elle refuse', function () {
        $client = User::factory()->client()->create();
        $available = productOnSale(['name' => 'Disponible']);
        $short = productOnSale(['name' => 'Épuisé', 'stock_quantity' => Quantity::fromInteger(1)]);

        cartOf($client, [[$available, Quantity::fromInteger(1)], [$short, Quantity::fromInteger(1)]]);

        $short->forceFill(['stock_quantity' => Quantity::zero()])->save();

        expect(fn () => app(OrderService::class)->place($client, app(CartService::class)->forClient($client)))
            ->toThrow(DomainException::class);

        // The first farmer's sub-order was already written when the second
        // line failed. The transaction is what rolls it back.
        expect(Order::query()->count())->toBe(0)
            ->and(SubOrder::query()->count())->toBe(0)
            ->and(app(CartService::class)->forClient($client)->items()->count())->toBe(2);
    });
});

describe('écran du panier', function () {
    it('emmène le client vers la commande créée', function () {
        $client = User::factory()->client()->create();
        cartOf($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->call('placeOrder')
            ->assertRedirect(route('client.orders.show', [
                'order' => Order::query()->firstOrFail()->reference,
            ]));
    });

    it('refuse de commander un panier dont une ligne est indisponible', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(5)]);

        cartOf($client, [[$product, Quantity::fromInteger(5)]]);
        $product->forceFill(['stock_quantity' => Quantity::fromInteger(1)])->save();

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->call('placeOrder')
            ->assertNoRedirect();

        expect(Order::query()->count())->toBe(0);
    });
});

it('donne à chaque sous-commande le statut en attente de paiement', function () {
    $client = User::factory()->client()->create();
    cartOf($client, [[productOnSale(), Quantity::fromInteger(1)]]);

    $order = app(OrderService::class)->place($client, app(CartService::class)->forClient($client));

    expect($order->subOrders->first()->status)->toBe(SubOrderStatus::PendingPayment);
});
