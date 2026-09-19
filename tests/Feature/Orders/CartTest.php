<?php

declare(strict_types=1);

use App\Livewire\Catalog\ProductPage;
use App\Livewire\Client\CartPage;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\CartService;
use App\Support\Quantity;
use Livewire\Livewire;

/**
 * The cart holds intent. Nothing it does touches stock or money.
 *
 * sellingFarmer() and productOnSale() live in tests/Helpers.php: three order
 * test files need them.
 */
describe('ajout au panier', function () {
    it('ajoute un produit au panier du client', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        app(CartService::class)->add($client, $product, Quantity::fromString('2.5'));

        $item = CartItem::query()->firstOrFail();

        expect($item->product_id)->toBe($product->id)
            ->and($item->quantity->toDecimalString())->toBe('2.500')
            // Business rule RG04: wanting something does not move it.
            ->and($product->refresh()->stock_quantity->toDecimalString())->toBe('20.000');
    });

    it('cumule la même référence au lieu de dupliquer la ligne', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $carts = app(CartService::class);

        $carts->add($client, $product, Quantity::fromInteger(2));
        $carts->add($client, $product, Quantity::fromInteger(3));

        expect(CartItem::query()->count())->toBe(1)
            ->and(CartItem::query()->firstOrFail()->quantity->toDecimalString())->toBe('5.000');
    });

    it('refuse une quantité supérieure au stock, cumul compris', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(4)]);
        $carts = app(CartService::class);

        $carts->add($client, $product, Quantity::fromInteger(3));

        expect(fn () => $carts->add($client, $product, Quantity::fromInteger(2)))
            ->toThrow(DomainException::class);

        expect(CartItem::query()->firstOrFail()->quantity->toDecimalString())->toBe('3.000');
    });

    it('refuse une quantité nulle ou négative', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        expect(fn () => app(CartService::class)->add($client, $product, Quantity::zero()))
            ->toThrow(DomainException::class);
    });

    it('refuse un produit qui n\'est pas publié', function () {
        $client = User::factory()->client()->create();
        $product = Product::factory()->draft()->forFarmer(sellingFarmer())->create();

        expect(fn () => app(CartService::class)->add($client, $product, Quantity::fromInteger(1)))
            ->toThrow(DomainException::class);
    });

    it('refuse le produit d\'un agriculteur suspendu', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        $product->farmer->suspend();

        expect(fn () => app(CartService::class)->add($client, $product->refresh(), Quantity::fromInteger(1)))
            ->toThrow(DomainException::class);
    });
});

describe('depuis la fiche produit', function () {
    it('envoie un visiteur se connecter plutôt que de le laisser commander', function () {
        $product = productOnSale();

        Livewire::test(ProductPage::class, ['product' => $product])
            ->set('quantity', '1')
            ->call('addToCart')
            ->assertRedirect(route('login'));

        expect(CartItem::query()->count())->toBe(0);
    });

    it('ajoute au panier pour un client connecté', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();

        Livewire::actingAs($client)
            ->test(ProductPage::class, ['product' => $product])
            ->set('quantity', '1,5')
            ->call('addToCart');

        expect(CartItem::query()->firstOrFail()->quantity->toDecimalString())->toBe('1.500');
    });

    it('refuse un agriculteur qui voudrait acheter', function () {
        $farmer = sellingFarmer();
        $product = productOnSale();

        Livewire::actingAs($farmer)
            ->test(ProductPage::class, ['product' => $product])
            ->call('addToCart')
            ->assertForbidden();
    });
});

describe('écran du panier', function () {
    it('affiche le total des lignes disponibles', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['unit_price' => 1_500]);

        app(CartService::class)->add($client, $product, Quantity::fromInteger(2));

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->assertSee('Tomates fraîches')
            ->assertSee('3');
    });

    it('met à jour une quantité saisie avec une virgule', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $item = app(CartService::class)->add($client, $product, Quantity::fromInteger(2));

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->set("quantities.{$item->id}", '3,5')
            ->call('updateQuantity', $item->id);

        expect($item->refresh()->quantity->toDecimalString())->toBe('3.500');
    });

    it('retire la ligne quand la quantité tombe à zéro', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $item = app(CartService::class)->add($client, $product, Quantity::fromInteger(2));

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->set("quantities.{$item->id}", '0')
            ->call('updateQuantity', $item->id);

        expect(CartItem::query()->count())->toBe(0);
    });

    it('ne laisse pas un client toucher la ligne d\'un autre', function () {
        $client = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        $product = productOnSale();

        $stranger = app(CartService::class)->add($other, $product, Quantity::fromInteger(2));

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->call('remove', $stranger->id)
            ->assertNotFound();

        expect(CartItem::query()->whereKey($stranger->id)->exists())->toBeTrue();
    });

    it('vide le panier sur demande', function () {
        $client = User::factory()->client()->create();
        app(CartService::class)->add($client, productOnSale(), Quantity::fromInteger(2));

        Livewire::actingAs($client)
            ->test(CartPage::class)
            ->call('clear');

        expect(CartItem::query()->count())->toBe(0);
    });
});
