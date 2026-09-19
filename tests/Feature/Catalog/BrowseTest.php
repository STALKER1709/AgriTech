<?php

declare(strict_types=1);

use App\Livewire\Catalog\Browse;
use App\Models\Category;
use App\Models\FarmerProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * What the public may see, and what it may not.
 */
function publishedProductOf(User $farmer, array $overrides = []): Product
{
    return Product::factory()->published()->forFarmer($farmer)->create($overrides);
}

function activeFarmerIn(string $region = 'Centre'): User
{
    $farmer = User::factory()->farmer()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id, 'region' => $region]);

    return $farmer;
}

it('is open to visitors', function () {
    $this->get(route('catalog.browse'))->assertOk();
});

describe('visibility', function () {
    it('shows only published products', function () {
        $farmer = activeFarmerIn();

        publishedProductOf($farmer, ['name' => 'Tomate visible']);
        Product::factory()->draft()->forFarmer($farmer)->create(['name' => 'Brouillon caché']);
        Product::factory()->inReview()->forFarmer($farmer)->create(['name' => 'En revue caché']);
        Product::factory()->rejected()->forFarmer($farmer)->create(['name' => 'Refusé caché']);
        Product::factory()->archived()->forFarmer($farmer)->create(['name' => 'Archivé caché']);

        Livewire::test(Browse::class)
            ->assertSee('Tomate visible')
            ->assertDontSee('Brouillon caché')
            ->assertDontSee('En revue caché')
            ->assertDontSee('Refusé caché')
            ->assertDontSee('Archivé caché');
    });

    it('hides the products of a suspended farmer', function () {
        $farmer = activeFarmerIn();
        publishedProductOf($farmer, ['name' => 'Miel des hauts plateaux']);

        Livewire::test(Browse::class)->assertSee('Miel des hauts plateaux');

        // Suspending an account has to stop it selling, otherwise the
        // suspension means nothing.
        $farmer->suspend();

        Livewire::test(Browse::class)->assertDontSee('Miel des hauts plateaux');
        $this->get(route('catalog.product', ['product' => Product::query()->sole()->slug]))
            ->assertNotFound();
    });

    it('hides the products of a deleted farmer', function () {
        $farmer = activeFarmerIn();
        publishedProductOf($farmer, ['name' => 'Cacao fermenté']);

        $farmer->anonymise();

        Livewire::test(Browse::class)->assertDontSee('Cacao fermenté');
    });

    it('answers 404 for a product that is not public', function () {
        $product = Product::factory()->draft()->forFarmer(activeFarmerIn())->create();

        $this->get(route('catalog.product', ['product' => $product->slug]))->assertNotFound();
    });
});

describe('search and filters', function () {
    beforeEach(function () {
        $this->centre = activeFarmerIn('Centre');
        $this->ouest = activeFarmerIn('Ouest');

        $this->legumes = Category::factory()->create(['name' => 'Légumes', 'slug' => 'legumes']);
        $this->fruits = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);

        publishedProductOf($this->centre, [
            'name' => 'Tomate du Centre', 'category_id' => $this->legumes->id, 'unit_price' => 800,
        ]);
        publishedProductOf($this->ouest, [
            'name' => 'Ananas de l\'Ouest', 'category_id' => $this->fruits->id, 'unit_price' => 1500,
        ]);
    });

    it('searches the name', function () {
        Livewire::test(Browse::class)
            ->set('search', 'Ananas')
            ->assertSee('Ananas')
            ->assertDontSee('Tomate du Centre');
    });

    it('filters by category', function () {
        Livewire::test(Browse::class)
            ->set('category', 'fruits')
            ->assertSee('Ananas')
            ->assertDontSee('Tomate du Centre');
    });

    it('filters by region', function () {
        Livewire::test(Browse::class)
            ->set('region', 'Centre')
            ->assertSee('Tomate du Centre')
            ->assertDontSee('Ananas');
    });

    it('sorts by price', function () {
        $ascending = Livewire::test(Browse::class)->set('sort', 'price_asc')->viewData('products');
        expect($ascending->first()->name)->toBe('Tomate du Centre');

        $descending = Livewire::test(Browse::class)->set('sort', 'price_desc')->viewData('products');
        expect($descending->first()->name)->toBe('Ananas de l\'Ouest');
    });

    it('clears every filter at once', function () {
        Livewire::test(Browse::class)
            ->set('search', 'Ananas')
            ->set('region', 'Ouest')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('region', '')
            ->assertSee('Tomate du Centre');
    });
});

it('does not run one query per product', function () {
    $farmer = activeFarmerIn();
    Product::factory()->published()->forFarmer($farmer)->count(8)->create();

    DB::enableQueryLog();
    Livewire::test(Browse::class)->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Eight products must not mean eight extra queries for their farmer and
    // their category. A fixed handful of eager loads is what this guards.
    expect($queries)->toBeLessThan(15);
});
