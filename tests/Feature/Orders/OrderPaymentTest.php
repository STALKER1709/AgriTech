<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubOrderStatus;
use App\Livewire\Client\OrderPage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPaid;
use App\Notifications\SubOrderReceived;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Business rules RG04 and RG06 on an order: stock moves only on a verified
 * confirmation, and only once.
 */
beforeEach(fn () => test()->seed(SettingSeeder::class));

describe('RG04 — le stock suit le paiement', function () {
    it('décrémente le stock à la confirmation vérifiée', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(20)]);
        $order = orderFor($client, [[$product, Quantity::fromString('2.5')]]);

        $payment = startOrderPayment($order);

        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('20.000');

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('17.500')
            ->and($order->refresh()->status)->toBe(OrderStatus::Paid)
            ->and($order->subOrders->first()->status)->toBe(SubOrderStatus::Paid)
            ->and($order->expires_at)->toBeNull();

        Notification::assertSentTo($client, OrderPaid::class);
        Notification::assertSentTo($product->farmer, SubOrderReceived::class);
    });

    it('ne décrémente pas deux fois quand le callback est rejoué', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(10)]);
        $order = orderFor($client, [[$product, Quantity::fromInteger(3)]]);
        $payment = startOrderPayment($order);

        $eventId = FakeMobileMoneyGateway::newEventId();

        deliverOrderCallback($payment, PaymentStatus::Succeeded, $eventId)->assertOk();
        deliverOrderCallback($payment, PaymentStatus::Succeeded, $eventId)->assertOk();

        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('7.000');
    });

    it('ne décrémente pas non plus sur un second callback au nouvel identifiant', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(10)]);
        $order = orderFor($client, [[$product, Quantity::fromInteger(3)]]);
        $payment = startOrderPayment($order);

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();
        // A distinct event id gets past the idempotence index; the payment's
        // own status machine is what stops the effect a second time.
        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('7.000');
    });

    it('annule la commande et laisse le stock tranquille quand le paiement échoue', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(10)]);
        $order = orderFor($client, [[$product, Quantity::fromInteger(3)]]);
        $payment = startOrderPayment($order);

        deliverOrderCallback($payment, PaymentStatus::Failed)->assertOk();

        expect($product->refresh()->stock_quantity->toDecimalString())->toBe('10.000')
            ->and($order->refresh()->status)->toBe(OrderStatus::Cancelled)
            ->and($order->subOrders->first()->status)->toBe(SubOrderStatus::Cancelled);

        Notification::assertSentTo($client, OrderCancelled::class);
    });
});

describe('stock disparu entre la commande et la confirmation', function () {
    it('annule la commande et rembourse le paiement', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['stock_quantity' => Quantity::fromInteger(5), 'name' => 'Dernier sac']);
        $order = orderFor($client, [[$product, Quantity::fromInteger(5)]]);
        $payment = startOrderPayment($order);

        // Someone else's order was confirmed first and took the lot.
        $product->forceFill(['stock_quantity' => Quantity::zero()])->save();

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
            ->and($payment->refresh()->status)->toBe(PaymentStatus::Refunded)
            // Never negative: business rule RG04's whole point.
            ->and($product->refresh()->stock_quantity->toDecimalString())->toBe('0.000');

        Notification::assertSentTo($client, OrderCancelled::class, function (OrderCancelled $notification): bool {
            return $notification->refunded === true
                && str_contains($notification->reason, 'Dernier sac');
        });
    });

    it('rembourse un paiement qui arrive après l\'annulation de la commande', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale();
        $order = orderFor($client, [[$product, Quantity::fromInteger(1)]]);
        $payment = startOrderPayment($order);

        $order->subOrders->first()->cancel();
        $order->cancel();

        deliverOrderCallback($payment, PaymentStatus::Succeeded)->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Refunded)
            ->and($order->refresh()->status)->toBe(OrderStatus::Cancelled)
            ->and($product->refresh()->stock_quantity->toDecimalString())->toBe('20.000');
    });
});

describe('RG06 — rien ne paie une commande hors du serveur', function () {
    it('ne paie pas la commande quand le navigateur revient de la passerelle', function () {
        $client = User::factory()->client()->create();
        $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);
        $payment = startOrderPayment($order);

        $this->actingAs($client)
            ->get(route('payments.pending', ['payment' => $payment->provider_reference]))
            ->assertOk();

        expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment)
            ->and($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    });

    it('refuse un callback dont le montant ne correspond pas', function () {
        Notification::fake();

        $client = User::factory()->client()->create();
        $product = productOnSale(['unit_price' => 1_000, 'stock_quantity' => Quantity::fromInteger(10)]);
        $order = orderFor($client, [[$product, Quantity::fromInteger(2)]]);
        $payment = startOrderPayment($order);

        $body = json_encode([
            'event_id' => FakeMobileMoneyGateway::newEventId(),
            'reference' => $payment->provider_reference,
            'status' => PaymentStatus::Succeeded->value,
            // A callback states an amount; the server compares it.
            'amount' => 1,
            'currency' => $payment->currency,
            'method' => $payment->method->value,
            'occurred_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) now()->getTimestamp();

        $this->call(
            method: 'POST',
            uri: route('webhooks.payment'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_AGRITECH_SIGNATURE' => FakeMobileMoneyGateway::sign($timestamp, $body),
                'HTTP_X_AGRITECH_TIMESTAMP' => $timestamp,
            ],
            content: $body,
        )->assertOk();

        expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment)
            ->and($product->refresh()->stock_quantity->toDecimalString())->toBe('10.000');
    });
});

describe('écran de la commande', function () {
    it('démarre le paiement du montant de la commande, pas d\'un montant reçu', function () {
        $client = User::factory()->client()->create();
        $order = orderFor($client, [[productOnSale(['unit_price' => 3_000]), Quantity::fromInteger(2)]]);

        Livewire::actingAs($client)
            ->test(OrderPage::class, ['order' => $order])
            ->set('phone', '+237670000001')
            ->call('pay');

        expect(Payment::query()->firstOrFail()->amount->amount)->toBe(6_000);
    });

    it('ne crée pas un second paiement quand le client clique deux fois', function () {
        $client = User::factory()->client()->create();
        $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        startOrderPayment($order);
        startOrderPayment($order);

        expect(Payment::query()->count())->toBe(1);
    });

    it('refuse de payer une commande déjà réglée', function () {
        $client = User::factory()->client()->create();
        $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        $order->subOrders->first()->markAsPaid();
        $order->markAsPaid();

        expect(fn () => startOrderPayment($order->refresh()))->toThrow(DomainException::class);
    });

    it('refuse de payer une commande expirée', function () {
        $client = User::factory()->client()->create();
        $order = orderFor($client, [[productOnSale(), Quantity::fromInteger(1)]]);

        $order->forceFill(['expires_at' => now()->subMinute()])->save();

        expect(fn () => startOrderPayment($order))->toThrow(DomainException::class);
    });

    it('ne laisse pas un client ouvrir la commande d\'un autre', function () {
        $client = User::factory()->client()->create();
        $other = User::factory()->client()->create();
        $order = orderFor($other, [[productOnSale(), Quantity::fromInteger(1)]]);

        $this->actingAs($client)
            ->get(route('client.orders.show', ['order' => $order->reference]))
            ->assertForbidden();
    });
});
