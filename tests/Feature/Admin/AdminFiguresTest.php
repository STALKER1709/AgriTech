<?php

declare(strict_types=1);

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\SubOrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Support\Quantity;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Les chiffres du tableau de bord d'administration. Chacun doit compter ce
 * qu'il annonce — le nom d'une méthode est une promesse.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PrivilegeSeeder::class);

    $this->admin = User::factory()->admin()->create();
});

/**
 * Un paiement confirmé, d'une nature et d'une date données.
 */
function settledPayment(int $amount, PaymentPurpose $purpose, ?string $confirmedAt = null): Payment
{
    return Payment::factory()->create([
        'amount' => $amount,
        'purpose' => $purpose,
        'status' => PaymentStatus::Succeeded,
        'confirmed_at' => $confirmedAt ?? now(),
    ]);
}

it('separates the platform commission from the volume it moved', function () {
    $client = User::factory()->client()->create();
    $product = productOnSale(['unit_price' => 10_000, 'stock_quantity' => Quantity::fromInteger(50)]);

    $order = orderFor($client, [[$product, Quantity::fromInteger(3)]]);
    $order->subOrders()->update(['status' => SubOrderStatus::Paid]);

    settledPayment(30_000, PaymentPurpose::Order);

    /** @var Dashboard $dashboard */
    $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

    // 5 % de 30 000 par défaut : la commission est figée sur la
    // sous-commande, le volume est ce que l'acheteur a réglé.
    expect($dashboard->platformCommission()->amount)->toBe(1_500);
    expect($dashboard->volumeSettled()->amount)->toBe(30_000);
});

it('leaves an unpaid sub-order out of the commission', function () {
    $client = User::factory()->client()->create();
    $product = productOnSale(['unit_price' => 10_000, 'stock_quantity' => Quantity::fromInteger(50)]);

    orderFor($client, [[$product, Quantity::fromInteger(3)]]);

    /** @var Dashboard $dashboard */
    $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

    expect($dashboard->platformCommission()->amount)->toBe(0);
});

describe('le volume mensuel', function () {
    it('rend six mois, le dernier étant celui en cours', function () {
        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

        $months = $dashboard->monthlyVolume();

        expect($months)->toHaveCount(6);
        expect($months[5]['label'])->toBe(now(config('app.timezone'))->startOfMonth()->translatedFormat('M'));
    });

    it('sépare les produits des formations et des abonnements', function () {
        settledPayment(30_000, PaymentPurpose::Order);
        settledPayment(7_500, PaymentPurpose::Training);
        settledPayment(12_000, PaymentPurpose::Subscription);

        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

        $current = $dashboard->monthlyVolume()[5];

        expect($current['orders']->amount)->toBe(30_000);
        expect($current['trainings']->amount)->toBe(19_500);
        expect($current['total']->amount)->toBe(49_500);
        expect($current['share'])->toBe(1.0);
    });

    it('ignore un paiement qui n\'a jamais abouti', function () {
        Payment::factory()->create([
            'amount' => 99_000,
            'purpose' => PaymentPurpose::Order,
            'status' => PaymentStatus::Failed,
            'confirmed_at' => null,
        ]);

        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

        expect($dashboard->monthlyVolume()[5]['total']->amount)->toBe(0);
    });

    it('compare les mois au plus haut, pas au total', function () {
        settledPayment(20_000, PaymentPurpose::Order);
        settledPayment(40_000, PaymentPurpose::Order, now()->subMonth()->startOfMonth()->addDay()->toDateTimeString());

        /** @var Dashboard $dashboard */
        $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

        $months = $dashboard->monthlyVolume();

        expect($months[4]['share'])->toBe(1.0);
        expect($months[5]['share'])->toBe(0.5);
    });
});

it('shows the newest audited actions, and no more than six', function () {
    foreach (range(1, 8) as $index) {
        AuditLog::create([
            'actor_id' => $this->admin->id,
            'action' => 'test.action.'.$index,
            'auditable_type' => User::class,
            'auditable_id' => $this->admin->id,
            'before' => null,
            'after' => ['index' => $index],
        ]);
    }

    /** @var Dashboard $dashboard */
    $dashboard = Livewire::actingAs($this->admin)->test(Dashboard::class)->instance();

    $entries = $dashboard->recentAudit();

    expect($entries)->toHaveCount(6);
    expect($entries->first()->action)->toBe('test.action.8');
});
