<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\User;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use App\Services\Orders\CartService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Orders\OrderService;
use App\Support\PhoneNumber;
use App\Support\Quantity;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Fixtures partagées
|--------------------------------------------------------------------------
|
| Autoloadées par Composer (`autoload-dev.files`) : un helper utilisé par
| plusieurs fichiers de test n'a pas à dépendre de l'ordre de chargement des
| fichiers de Pest.
|
*/

/**
 * An active farmer with a farm profile: the only kind that may sell.
 */
function sellingFarmer(): User
{
    $farmer = User::factory()->farmer()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    return $farmer;
}

/**
 * A published product with stock, priced in whole francs.
 *
 * @param  array<string, mixed>  $overrides
 */
function productOnSale(array $overrides = []): Product
{
    return Product::factory()
        ->published()
        ->forFarmer(sellingFarmer())
        ->create(array_merge([
            'name' => 'Tomates fraîches',
            'unit' => ProductUnit::Kilogram,
            'unit_price' => 1_000,
            'stock_quantity' => Quantity::fromInteger(20),
        ], $overrides));
}

/**
 * @param  array<int, array{0: Product, 1: Quantity}>  $lines
 */
function orderFor(User $client, array $lines): Order
{
    $carts = app(CartService::class);

    foreach ($lines as [$product, $quantity]) {
        $carts->add($client, $product, $quantity);
    }

    return app(OrderService::class)->place($client, $carts->forClient($client));
}

function startOrderPayment(Order $order): Payment
{
    app(OrderPaymentService::class)->start(
        $order,
        PaymentMethod::MtnMomo,
        PhoneNumber::parse('+237670000001'),
    );

    return Payment::query()->latest('id')->firstOrFail();
}

function deliverOrderCallback(Payment $payment, PaymentStatus $status, ?string $eventId = null): TestResponse
{
    $body = json_encode([
        'event_id' => $eventId ?? FakeMobileMoneyGateway::newEventId(),
        'reference' => $payment->provider_reference,
        'status' => $status->value,
        'amount' => $payment->amount->amount,
        'currency' => $payment->currency,
        'method' => $payment->method->value,
        'occurred_at' => now()->toIso8601String(),
    ], JSON_THROW_ON_ERROR);

    $timestamp = (string) now()->getTimestamp();

    return test()->call(
        method: 'POST',
        uri: route('webhooks.payment'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_AGRITECH_SIGNATURE' => FakeMobileMoneyGateway::sign($timestamp, $body),
            'HTTP_X_AGRITECH_TIMESTAMP' => $timestamp,
        ],
        content: $body,
    );
}

/**
 * An administrator holding exactly the given privileges: the RG07 test
 * fixture. Shared here because several admin test files use it, and in
 * parallel runs each process loads helpers independently of file order.
 */
function adminWith(string ...$codes): User
{
    $admin = User::factory()->admin()->create();

    $admin->privileges()->attach(
        Privilege::query()->whereIn('code', $codes)->pluck('id'),
    );

    return $admin;
}
