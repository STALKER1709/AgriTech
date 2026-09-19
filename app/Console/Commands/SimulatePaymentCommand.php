<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Jobs\DeliverSimulatedCallback;
use App\Models\Payment;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Console\Command;

/**
 * Forces an outcome on a payment, from the terminal.
 *
 * Useful for what a browser makes tedious: replaying a refusal, closing a
 * payment nobody answered, and above all sending the same callback twice to
 * prove the webhook is idempotent.
 */
final class SimulatePaymentCommand extends Command
{
    protected $signature = 'agritech:payment:simulate
        {reference : La référence du paiement (PAY-…)}
        {outcome : succeeded, failed ou expired}
        {--duplicate : Envoie deux fois le même callback, pour tester l\'idempotence}
        {--now : Délivre immédiatement, sans la latence configurée}';

    protected $description = 'Force l\'issue d\'un paiement via la passerelle simulée';

    public function handle(): int
    {
        $payment = Payment::query()
            ->where('provider_reference', $this->argument('reference'))
            ->first();

        if (! $payment instanceof Payment) {
            $this->components->error(sprintf(
                'Aucun paiement ne porte la référence [%s].',
                (string) $this->argument('reference'),
            ));

            return self::FAILURE;
        }

        $outcome = PaymentStatus::tryFrom((string) $this->argument('outcome'));

        if ($outcome === null || ! in_array($outcome, [PaymentStatus::Succeeded, PaymentStatus::Failed, PaymentStatus::Expired], strict: true)) {
            $this->components->error('L\'issue doit être succeeded, failed ou expired.');

            return self::FAILURE;
        }

        // An expiry is the absence of an answer: the reconciliation task
        // closes the payment, no callback is ever sent.
        if ($outcome === PaymentStatus::Expired) {
            $this->components->info(sprintf(
                'Aucun callback envoyé : une expiration est l\'absence de réponse. Lancez `php artisan agritech:payments:reconcile` après %d minutes, ou avancez la date de création du paiement.',
                (int) config('payments.expiration_minutes', 15),
            ));

            return self::SUCCESS;
        }

        $delay = $this->option('now')
            ? 0
            : (int) config('payments.callback_delay_seconds', 5);

        // Deliberately the same event id, since that is exactly what a replay
        // is: the gateway resending the callback it already sent.
        $eventId = FakeMobileMoneyGateway::newEventId();
        $deliveries = $this->option('duplicate') ? 2 : 1;

        foreach (range(1, $deliveries) as $attempt) {
            DeliverSimulatedCallback::dispatch($payment->provider_reference, $outcome, $eventId)
                ->delay(now()->addSeconds($delay * $attempt));
        }

        $this->components->info(sprintf(
            '%d callback%s [%s] mis en file pour %s%s.',
            $deliveries,
            $deliveries > 1 ? 's' : '',
            $outcome->value,
            $payment->provider_reference,
            $delay > 0 ? sprintf(' (délai : %d s)', $delay) : '',
        ));

        if ($delay > 0) {
            $this->components->warn('`php artisan queue:work` doit tourner pour que le callback soit délivré.');
        }

        return self::SUCCESS;
    }
}
