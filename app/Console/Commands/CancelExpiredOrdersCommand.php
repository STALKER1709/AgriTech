<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Orders\OrderFulfilmentService;
use Illuminate\Console\Command;

/**
 * Closes orders that were placed and then abandoned.
 *
 * An unpaid order holds nothing — business rule RG04 keeps the stock where it
 * is until a payment is confirmed — so this is housekeeping rather than a
 * release. It still matters: a client's order list otherwise fills up with
 * carts they never paid for.
 *
 * An order whose payment is still awaiting an answer is left alone. The
 * reconciliation task settles those first, and cancelling underneath it would
 * turn an ordinary slow payment into a refund.
 */
final class CancelExpiredOrdersCommand extends Command
{
    protected $signature = 'agritech:orders:cancel-expired';

    protected $description = 'Annule les commandes restées impayées au-delà de leur délai';

    public function handle(OrderFulfilmentService $fulfilment): int
    {
        $expired = Order::query()
            ->expired()
            ->whereDoesntHave('payments', fn ($payments) => $payments->awaitingOutcome())
            ->get();

        if ($expired->isEmpty()) {
            $this->components->info('Aucune commande impayée à annuler.');

            return self::SUCCESS;
        }

        $cancelled = 0;

        foreach ($expired as $order) {
            if ($fulfilment->cancelExpired($order)) {
                $cancelled++;

                $this->components->twoColumnDetail($order->reference, 'annulée');
            }
        }

        $this->components->info(sprintf(
            '%d commande%s annulée%s.',
            $cancelled,
            $cancelled > 1 ? 's' : '',
            $cancelled > 1 ? 's' : '',
        ));

        return self::SUCCESS;
    }
}
