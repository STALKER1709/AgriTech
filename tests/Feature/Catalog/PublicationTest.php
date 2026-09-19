<?php

declare(strict_types=1);

use App\Enums\PublicationStatus;
use App\Livewire\Farmer\ProductForm;
use App\Livewire\Farmer\ProductList;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Catalog\PublicationService;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Business rules RG01 (an inactive farmer publishes nothing) and RG09 (a
 * publication reaches the catalogue through moderation).
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);

    $this->farmer = User::factory()->farmer()->create();
    $this->category = Category::factory()->create();
});

function productPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Tomate fraîche de saison',
        'description' => 'Tomates cultivées en plein champ, récoltées à maturité, livrées le jour même.',
        'unit_price' => '800',
        'unit' => 'kg',
        'stock_quantity' => '120.500',
    ], $overrides);
}

describe('RG01 — publishing needs an active account', function () {
    it('lets an active farmer create a draft', function () {
        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class)
            ->set('category_id', $this->category->id)
            ->set(productPayload())
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->sole();

        expect($product->status)->toBe(PublicationStatus::Draft);
        expect($product->farmer_id)->toBe($this->farmer->id);
    });

    it('refuses the form to a farmer awaiting validation', function () {
        $pending = User::factory()->awaitingValidation()->create();

        Livewire::actingAs($pending)
            ->test(ProductForm::class)
            ->assertForbidden();
    });

    it('refuses the form to a suspended farmer', function () {
        $suspended = User::factory()->farmer()->suspended()->create();

        Livewire::actingAs($suspended)->test(ProductForm::class)->assertForbidden();
    });

    it('refuses the service too, not only the screen', function () {
        $suspended = User::factory()->farmer()->suspended()->create();
        $product = Product::factory()->draft()->forFarmer($suspended)->create();

        app(PublicationService::class)->submit($product, $suspended);
    })->throws(DomainException::class);

    it('never lets a farmer touch another farmer\'s product', function () {
        $someoneElse = Product::factory()->draft()->create();

        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class, ['product' => $someoneElse])
            ->assertForbidden();
    });
});

describe('RG09 — moderation before publication', function () {
    it('sends a submitted product to review', function () {
        $product = Product::factory()->draft()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(ProductList::class)
            ->call('submit', $product->id);

        expect($product->refresh()->status)->toBe(PublicationStatus::InReview);
    });

    it('publishes straight away when prior moderation is switched off', function () {
        Setting::query()
            ->where('key', Setting::PRIOR_MODERATION_ENABLED)
            ->update(['value' => '0']);

        $product = Product::factory()->draft()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(ProductList::class)
            ->call('submit', $product->id);

        expect($product->refresh()->status)->toBe(PublicationStatus::Published);
    });

    it('lets a rejected product be corrected and submitted again', function () {
        $product = Product::factory()->rejected()->forFarmer($this->farmer)->create([
            'rejection_reason' => 'Photo illisible.',
        ]);

        Livewire::actingAs($this->farmer)
            ->test(ProductList::class)
            ->call('submit', $product->id);

        expect($product->refresh()->status)->toBe(PublicationStatus::InReview);

        // The old reason goes: it applied to the version that was refused.
        expect($product->rejection_reason)->toBeNull();
    });

    it('refuses to submit a product already under review', function () {
        $product = Product::factory()->inReview()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(ProductList::class)
            ->call('submit', $product->id)
            ->assertForbidden();
    });
});

describe('validation', function () {
    it('demands every field the catalogue needs', function (string $field) {
        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class)
            ->set('category_id', $this->category->id)
            ->set(productPayload([$field => '']))
            ->call('save')
            ->assertHasErrors($field);
    })->with(['name', 'description', 'unit_price', 'stock_quantity']);

    it('refuses a price with decimals, per business rule RG10', function () {
        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class)
            ->set('category_id', $this->category->id)
            ->set(productPayload(['unit_price' => '800.50']))
            ->call('save')
            ->assertHasErrors('unit_price');
    });

    it('refuses a quantity with more than three decimals', function () {
        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class)
            ->set('category_id', $this->category->id)
            ->set(productPayload(['stock_quantity' => '12.5001']))
            ->call('save')
            ->assertHasErrors('stock_quantity');
    });

    it('refuses a category that does not exist', function () {
        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class)
            ->set('category_id', 99999)
            ->set(productPayload())
            ->call('save')
            ->assertHasErrors('category_id');
    });
});

describe('slugs', function () {
    it('keeps identical product names apart', function () {
        $second = User::factory()->farmer()->create();
        $third = User::factory()->farmer()->create();

        foreach ([$this->farmer, $second, $third] as $seller) {
            Livewire::actingAs($seller)
                ->test(ProductForm::class)
                ->set('category_id', $this->category->id)
                ->set(productPayload())
                ->call('save')
                ->assertHasNoErrors();
        }

        $slugs = Product::query()->pluck('slug')->all();

        expect($slugs)->toHaveCount(3);
        expect(array_unique($slugs))->toHaveCount(3);
        expect($slugs)->toContain('tomate-fraiche-de-saison');
    });

    it('leaves the slug alone when the name has not changed', function () {
        $product = Product::factory()->draft()->forFarmer($this->farmer)->create([
            'name' => 'Tomate fraîche de saison',
            'slug' => 'tomate-fraiche-de-saison',
        ]);

        Livewire::actingAs($this->farmer)
            ->test(ProductForm::class, ['product' => $product])
            ->set('description', 'Une description entièrement réécrite pour ce produit de saison.')
            ->call('save');

        expect($product->refresh()->slug)->toBe('tomate-fraiche-de-saison');
    });
});
