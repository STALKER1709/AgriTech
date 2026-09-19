<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

/**
 * Chases up payments nobody ever answered.
 *
 * Callbacks get lost: a queue worker dies, a network drops, a browser closes
 * mid-flow. Without this, such a payment would sit awaiting an outcome for
 * ever, and a farmer who actually paid would stay blocked.
 *
 * Business rule RG06 allows exactly two server-side routes to a confirmed
 * payment: a verified callback, and this.
 */
final class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'agritech:payments:reconcile
        {--minutes= : Fenêtre d\'attente, en minutes, au-delà de laquelle un paiement sans réponse est expiré}';

    protected $description = 'Interroge la passerelle pour les paiements restés sans réponse';

    public function handle(PaymentService $payments): int
    {
        $minutes = $this->option('minutes') !== null
            ? (int) $this->option('minutes')
            : (int) config('payments.expiration_minutes', 15);

        $stale = Payment::query()
            ->awaitingOutcome()
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        if ($stale->isEmpty()) {
            $this->components->info('Aucun paiement en attente à réconcilier.');

            return self::SUCCESS;
        }

        $settled = 0;

        foreach ($stale as $payment) {
            $before = $payment->status;
            $after = $payments->reconcile($payment, $minutes);

            if ($after !== $before) {
                $settled++;

                $this->components->twoColumnDetail(
                    $payment->provider_reference,
                    sprintf('%s → %s', $before->value, $after->value),
                );
            }
        }

        $this->components->info(sprintf(
            '%d paiement%s examiné%s, %d clôturé%s.',
            $stale->count(),
            $stale->count() > 1 ? 's' : '',
            $stale->count() > 1 ? 's' : '',
            $settled,
            $settled > 1 ? 's' : '',
        ));

        return self::SUCCESS;
    }
}
