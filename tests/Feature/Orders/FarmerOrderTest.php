<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubOrderStatus;
use App\Livewire\Farmer\OrderList;
use App\Models\Order;
use App\Models\SubOrder;
use App\Models\User;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * What a farmer sees of an order, and what they may do with it.
 */
beforeEach(fn () => test()->seed(SettingSeeder::class));

/**
 * A paid order, confirmed the only way business rule RG06 allows.
 */
function paidOrderFor(User $client, array $lines): Order
{
    Notification::fake();

    $order = orderFor($client, $lines);
    $payment = startOrderPayment($order);

    deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

    return $order->refresh();
}

describe('visibilité', function () {
    it('montre à l\'agriculteur sa part et rien d\'autre', function () {
        $client = User::factory()->client()->create();
        $mine = productOnSale(['name' => 'Mes tomates']);
        $theirs = productOnSale(['name' => 'Leur miel']);

        paidOrderFor($client, [
            [$mine, Quantity::fromInteger(1)],
            [$theirs, Quantity::fromInteger(1)],
        ]);

        Livewire::actingAs($mine->farmer)
            ->test(OrderList::class)
            ->assertSee('Mes tomates')
            ->assertDontSee('Leur miel');
    });

    it('n\'affiche pas une sous-commande annulée, qui n\'a jamais été du travail', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['name' => 'Jamais payé']);

        $order = orderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $payment = startOrderPayment($order);

        Notification::fake();
        deliverOrderCallback($payment, PaymentStatus::Failed)->assertOk();

        Livewire::actingAs($product->farmer)
            ->test(OrderList::class)
            ->assertDontSee('Jamais payé');
    });

    it('n\'affiche pas une sous-commande encore impayée', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale(['name' => 'Pas encore payé']);

        orderFor($client, [[$product, Quantity::fromInteger(1)]]);

        Livewire::actingAs($product->farmer)
            ->test(OrderList::class)
            ->assertDontSee('Pas encore payé');
    });

    it('refuse l\'écran à un client', function () {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('farmer.orders'))
            ->assertForbidden();
    });
});

describe('avancement', function () {
    it('passe la sous-commande en préparation puis en livrée', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $order = paidOrderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $subOrder = $order->subOrders->first();

        Livewire::actingAs($product->farmer)
            ->test(OrderList::class)
            ->call('prepare', $subOrder->id);

        expect($subOrder->refresh()->status)->toBe(SubOrderStatus::Preparing)
            ->and($order->refresh()->status)->toBe(OrderStatus::Preparing);

        Livewire::actingAs($product->farmer)
            ->test(OrderList::class)
            ->call('deliver', $subOrder->id);

        expect($subOrder->refresh()->status)->toBe(SubOrderStatus::Delivered)
            ->and($order->refresh()->status)->toBe(OrderStatus::Delivered);
    });

    it('ne livre la commande que lorsque tous les agriculteurs ont livré', function () {
        $client = User::factory()->client()->create();
        $first = productOnSale(['name' => 'Tomates']);
        $second = productOnSale(['name' => 'Miel']);

        $order = paidOrderFor($client, [
            [$first, Quantity::fromInteger(1)],
            [$second, Quantity::fromInteger(1)],
        ]);

        $firstSubOrder = $order->subOrders->firstWhere('farmer_id', $first->farmer_id);
        $secondSubOrder = $order->subOrders->firstWhere('farmer_id', $second->farmer_id);

        Livewire::actingAs($first->farmer)->test(OrderList::class)->call('deliver', $firstSubOrder->id);

        expect($order->refresh()->status)->toBe(OrderStatus::Preparing);

        Livewire::actingAs($second->farmer)->test(OrderList::class)->call('deliver', $secondSubOrder->id);

        expect($order->refresh()->status)->toBe(OrderStatus::Delivered);
    });

    it('refuse qu\'un agriculteur avance la part d\'un autre', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $order = paidOrderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $subOrder = $order->subOrders->first();

        $intruder = sellingFarmer();

        Livewire::actingAs($intruder)
            ->test(OrderList::class)
            ->call('prepare', $subOrder->id)
            ->assertForbidden();

        expect($subOrder->refresh()->status)->toBe(SubOrderStatus::Paid);
    });

    it('refuse qu\'un agriculteur suspendu avance sa part', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $order = paidOrderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $subOrder = $order->subOrders->first();

        $product->farmer->suspend();

        expect(
            $product->farmer->refresh()->can('prepare', $subOrder)
        )->toBeFalse();
    });

    it('refuse de préparer une part non payée', function () {
        $client = User::factory()->client()->create();
        $product = productOnSale();
        $order = orderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $subOrder = $order->subOrders->first();

        expect($product->farmer->can('prepare', $subOrder))->toBeFalse();
    });
});

it('montre à l\'agriculteur ce qui lui revient, commission déduite', function () {
    $client = User::factory()->client()->create();
    $product = productOnSale(['unit_price' => 10_000]);
    $order = paidOrderFor($client, [[$product, Quantity::fromInteger(1)]]);

    /** @var SubOrder $subOrder */
    $subOrder = $order->subOrders->first();

    expect($subOrder->commission_amount->amount)->toBe(500)
        ->and($subOrder->farmerPayout()->amount)->toBe(9_500)
        ->and($subOrder->farmerPayout()->amount)->toBeMoney();
});
