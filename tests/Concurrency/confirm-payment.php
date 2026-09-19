<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Worker de test de concurrence
|--------------------------------------------------------------------------
|
| Lancé en sous-processus par StockRaceTest : il démarre l'application, se
| met en attente jusqu'à un instant convenu, puis délivre le callback de la
| passerelle. Deux processus partant du même instant se disputent réellement
| le verrou de ligne, ce qu'un seul processus PHP ne peut pas simuler.
|
| Usage : php confirm-payment.php <référence-paiement> <instant-barrière>
|
*/

use App\Enums\PaymentStatus;
use App\Jobs\DeliverSimulatedCallback;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/../../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$reference = (string) ($argv[1] ?? '');
$barrier = (float) ($argv[2] ?? 0);

// Open the connection before the barrier, so what the two processes race for
// is the row lock and not a TCP handshake.
DB::connection()->getPdo();

while (microtime(true) < $barrier) {
    usleep(100);
}

try {
    (new DeliverSimulatedCallback(
        $reference,
        PaymentStatus::Succeeded,
        FakeMobileMoneyGateway::newEventId(),
    ))->handle();

    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");

    exit(1);
}
