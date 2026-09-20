<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Models\Payment;
use App\Models\Subscription;
use App\Payments\Exceptions\PaymentOutcomeNotHandled;

/**
 * What a paid subscription does: start the term.
 *
 * The clock only starts once the payment is confirmed, so a client never loses
 * days waiting for the gateway. A confirmation arriving after the client gave
 * up — a reconciliation finding a subscription the sweep already cancelled —
 * is not an error, just the same outcome arriving twice, and there is nothing
 * left to do.
 */
final class SubscriptionOutcome implements HandlesPaymentOutcome
{
    public function onSucceeded(Payment $payment): void
    {
        $subscription = $this->subscription($payment);

        if ($subscription->status->grantsAccess()) {
            return;
        }

        $subscription->activate();
    }

    public function onUnsuccessful(Payment $payment): void
    {
        // A subscription that was never paid for stays where it is: an
        // abandoned pending_payment row, harmless and truthful.
    }

    /**
     * A payment whose purpose is Subscription but which points at nothing —
     * or at something else — is a bug, not a case to absorb quietly.
     */
    private function subscription(Payment $payment): Subscription
    {
        $payable = $payment->payable;

        if (! $payable instanceof Subscription) {
            throw PaymentOutcomeNotHandled::forPurpose($payment->purpose);
        }

        return $payable;
    }
}
