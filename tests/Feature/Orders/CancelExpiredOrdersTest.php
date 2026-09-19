<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubOrderStatus;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

/**
 * The scheduled sweep that closes orders nobody paid for.
 */
beforeEach(fn () => test()->seed(SettingSeeder::class));

it('annule une commande impayée dont le délai est dépassé', function () {
    Notification::fake();

    $client = User::factory()->client()->create();
    $product = productOnSale();
    $order = orderFor($client, [[$product, Quantity::fromInteger(2)]]);

    $order->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->artisan('agritech:orders:cancel-expired')->assertSuccessful();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->subOrders->first()->status)->toBe(SubOrderStatus::Cancelled)
        // Nothing was held, so nothing comes back.
        ->and($product->refresh()->stock_quantity->toDecimalString())->toBe('20.000');

    Notification::assertSentTo($client, OrderCancelled::class);
});

it('laisse tranquille une commande dont le délai court encore', function () {
    $client = User::factory()->client()->create();
    $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);

    $this->artisan('agritech:orders:cancel-expired')->assertSuccessful();

    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('laisse tranquille une commande dont le paiement est en cours de vérification', function () {
    $client = User::factory()->client()->create();
    $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);
    $payment = startOrderPayment($order);

    $order->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->artisan('agritech:orders:cancel-expired')->assertSuccessful();

    // Cancelling under a payment still in flight turns an ordinary slow
    // confirmation into a refund. The reconciliation task settles it first.
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});

it('ne touche pas à une commande déjà payée', function () {
    Notification::fake();

    $client = User::factory()->client()->create();
    $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);

    $order->subOrders->first()->markAsPaid();
    $order->markAsPaid();
    $order->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->artisan('agritech:orders:cancel-expired')->assertSuccessful();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid);

    Notification::assertNothingSent();
});

it('est planifiée toutes les cinq minutes', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains((string) $event->command, 'agritech:orders:cancel-expired'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('*/5 * * * *');
});
