<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Business rule RG04, under real contention.
 *
 * Two confirmations for the last crate, delivered by two operating-system
 * processes with their own connections, released at the same instant. One
 * PHP process cannot reproduce this: it would serialise the two by
 * construction and prove nothing about the row lock.
 */
beforeEach(fn () => test()->seed(SettingSeeder::class));

/**
 * The environment a worker needs to reach the very database this test is
 * using — which is not the one .env points at, and is suffixed when the
 * suite runs in parallel.
 *
 * @return array<string, string>
 */
function workerEnvironment(): array
{
    return [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => (string) config('database.connections.mysql.database'),
        'DB_URL' => '',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'PAYMENT_GATEWAY' => 'fake',
        'PAYMENT_WEBHOOK_SECRET' => (string) config('payments.webhook_secret'),
        'PAYMENT_CALLBACK_DELAY_SECONDS' => '0',
    ];
}

/**
 * Release both confirmations at the same instant and wait for both to finish.
 *
 * @param  array<int, string>  $references
 * @return array<int, Process>
 */
function confirmTogether(array $references): array
{
    $barrier = microtime(true) + 2.0;

    $processes = array_map(
        fn (string $reference): Process => new Process(
            command: [PHP_BINARY, base_path('tests/Concurrency/confirm-payment.php'), $reference, (string) $barrier],
            cwd: base_path(),
            env: workerEnvironment(),
            timeout: 60,
        ),
        $references,
    );

    foreach ($processes as $process) {
        $process->start();
    }

    foreach ($processes as $process) {
        $process->wait();
    }

    return $processes;
}

it('ne laisse jamais deux paiements simultanés rendre le stock négatif', function () {
    $product = productOnSale([
        'name' => 'Dernier régime de bananes',
        'unit_price' => 5_000,
        // Enough for one order, not for two.
        'stock_quantity' => Quantity::fromInteger(10),
    ]);

    $first = orderFor(User::factory()->client()->create(), [[$product, Quantity::fromInteger(10)]]);
    $second = orderFor(User::factory()->client()->create(), [[$product, Quantity::fromInteger(10)]]);

    $firstPayment = startOrderPayment($first);
    $secondPayment = startOrderPayment($second);

    $processes = confirmTogether([
        $firstPayment->provider_reference,
        $secondPayment->provider_reference,
    ]);

    foreach ($processes as $process) {
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    }

    $stock = DB::table('products')->where('id', $product->id)->value('stock_quantity');

    expect((float) $stock)->toBe(0.0);

    $statuses = Order::query()->pluck('status', 'id')->values();

    expect($statuses->filter(fn (OrderStatus $status): bool => $status === OrderStatus::Paid))->toHaveCount(1)
        ->and($statuses->filter(fn (OrderStatus $status): bool => $status === OrderStatus::Cancelled))->toHaveCount(1);

    // The one that lost the race is refunded, not left holding a payment for
    // goods it will never receive.
    $refunded = Payment::query()->where('status', PaymentStatus::Refunded)->count();
    $succeeded = Payment::query()->where('status', PaymentStatus::Succeeded)->count();

    expect($refunded)->toBe(1)
        ->and($succeeded)->toBe(1);
});

it('sert les deux commandes quand le stock suffit pour les deux', function () {
    $product = productOnSale([
        'name' => 'Sacs de maïs',
        'unit_price' => 2_000,
        'stock_quantity' => Quantity::fromInteger(20),
    ]);

    $first = orderFor(User::factory()->client()->create(), [[$product, Quantity::fromInteger(10)]]);
    $second = orderFor(User::factory()->client()->create(), [[$product, Quantity::fromInteger(10)]]);

    confirmTogether([
        startOrderPayment($first)->provider_reference,
        startOrderPayment($second)->provider_reference,
    ]);

    $stock = DB::table('products')->where('id', $product->id)->value('stock_quantity');

    // Both decrements applied, neither lost: the lock serialises, it does not
    // discard.
    expect((float) $stock)->toBe(0.0)
        ->and(Order::query()->where('status', OrderStatus::Paid)->count())->toBe(2);
});
