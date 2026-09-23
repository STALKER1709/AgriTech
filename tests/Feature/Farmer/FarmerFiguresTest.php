<?php

declare(strict_types=1);

use App\Enums\SubOrderStatus;
use App\Livewire\Farmer\Dashboard;
use App\Livewire\Farmer\ProductList;
use App\Livewire\Farmer\TrainingList;
use App\Models\Order;
use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Les chiffres des écrans de l'agriculteur. Chacun doit compter ce qu'il
 * annonce : ni plus, ni dans une autre unité.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);

    $this->farmer = sellingFarmer();
    $this->client = User::factory()->client()->create();
});

/**
 * Une commande payée, chez cet agriculteur, à la date voulue.
 */
function paidSaleFor(User $farmer, User $client, string $quantity, ?string $at = null): Order
{
    $product = productOnSale(['stock_quantity' => Quantity::fromInteger(500)]);
    $product->farmer_id = $farmer->id;
    $product->save();

    $order = orderFor($client, [[$product, Quantity::fromString($quantity)]]);

    $subOrder = $order->subOrders()->sole();
    $subOrder->status = SubOrderStatus::Paid;

    if ($at !== null) {
        $subOrder->created_at = $at;
    }

    $subOrder->save();

    return $order;
}

describe('les quantités vendues ce mois-ci', function () {
    it('compte dans l\'unité du produit, pas en millièmes', function () {
        // La colonne `order_items.quantity` est un decimal(12,3) : sa somme
        // revient « 12.500 », pas 12500. La convertir en millièmes
        // diviserait tout par mille en silence.
        $order = paidSaleFor($this->farmer, $this->client, '12.5');
        $productId = $order->subOrders()->sole()->items()->sole()->product_id;

        /** @var ProductList $list */
        $list = Livewire::actingAs($this->farmer)->test(ProductList::class)->instance();

        expect($list->soldThisMonth()[$productId]->format())->toBe('12,5');
    });

    it('ignore une sous-commande qui n\'a pas été payée', function () {
        $order = paidSaleFor($this->farmer, $this->client, '4');
        $order->subOrders()->sole()->update(['status' => SubOrderStatus::Cancelled]);

        /** @var ProductList $list */
        $list = Livewire::actingAs($this->farmer)->test(ProductList::class)->instance();

        expect($list->soldThisMonth())->toBe([]);
    });

    it('ignore une vente du mois précédent', function () {
        paidSaleFor($this->farmer, $this->client, '4', now()->subMonth()->startOfMonth()->toDateTimeString());

        /** @var ProductList $list */
        $list = Livewire::actingAs($this->farmer)->test(ProductList::class)->instance();

        expect($list->soldThisMonth())->toBe([]);
    });

    it('ne compte jamais la vente d\'un autre agriculteur', function () {
        paidSaleFor(sellingFarmer(), $this->client, '4');

        /** @var ProductList $list */
        $list = Livewire::actingAs($this->farmer)->test(ProductList::class)->instance();

        expect($list->soldThisMonth())->toBe([]);
    });
});

describe('les ventes hebdomadaires', function () {
    it('rend quatre semaines, la dernière étant celle en cours', function () {
        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->farmer)->test(Dashboard::class)->instance();

        $weeks = $dashboard->weeklySales();

        expect($weeks)->toHaveCount(4);
        expect($weeks[3]['label'])->toBe(now(config('app.timezone'))->startOfWeek()->translatedFormat('d M'));
    });

    it('place une vente dans sa semaine et laisse les autres à zéro', function () {
        paidSaleFor($this->farmer, $this->client, '3');

        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->farmer)->test(Dashboard::class)->instance();

        $weeks = $dashboard->weeklySales();

        expect($weeks[3]['amount']->amount)->toBeGreaterThan(0);
        expect($weeks[3]['share'])->toBe(1.0);

        foreach ([0, 1, 2] as $index) {
            expect($weeks[$index]['amount']->amount)->toBe(0);
            expect($weeks[$index]['share'])->toBe(0.0);
        }
    });

    it('compare les semaines à la plus haute, pas au total', function () {
        paidSaleFor($this->farmer, $this->client, '2');
        paidSaleFor($this->farmer, $this->client, '4', now()->subWeek()->startOfWeek()->addDay()->toDateTimeString());

        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->farmer)->test(Dashboard::class)->instance();

        $weeks = $dashboard->weeklySales();

        // La semaine à 4 unités vaut le double de celle à 2 : la plus haute
        // occupe toute la hauteur, l'autre la moitié.
        expect($weeks[2]['share'])->toBe(1.0);
        expect($weeks[3]['share'])->toBe(0.5);
    });
});

describe('les recettes de formation', function () {
    it('additionne ce qui a été payé, sans retenue', function () {
        $training = Training::factory()->published()->create(['farmer_id' => $this->farmer->id]);

        foreach ([5_000, 7_500] as $amount) {
            TrainingPurchase::factory()->create([
                'training_id' => $training->id,
                'client_id' => User::factory()->client()->create()->id,
                'amount' => $amount,
            ]);
        }

        /** @var TrainingList $list */
        $list = Livewire::actingAs($this->farmer)->test(TrainingList::class)->instance();

        expect($list->earnings()['collected']->amount)->toBe(12_500);
        expect($list->earnings()['buyers'])->toBe(2);
    });

    it('ne compte pas les formations d\'un autre agriculteur', function () {
        $other = Training::factory()->published()->create(['farmer_id' => sellingFarmer()->id]);

        TrainingPurchase::factory()->create([
            'training_id' => $other->id,
            'client_id' => $this->client->id,
            'amount' => 9_000,
        ]);

        /** @var TrainingList $list */
        $list = Livewire::actingAs($this->farmer)->test(TrainingList::class)->instance();

        expect($list->earnings()['collected']->amount)->toBe(0);
        expect($list->earnings()['buyers'])->toBe(0);
    });
});
