<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Models\Payment;

/**
 * The business effect of a payment reaching a final state.
 *
 * One implementation per purpose. Keeping the effect out of the webhook
 * controller is what lets phase 6 add orders without touching the code that
 * verifies callbacks.
 */
interface HandlesPaymentOutcome
{
    /**
     * Called once, inside the transaction that confirmed the payment.
     */
    public function onSucceeded(Payment $payment): void;

    /**
     * Called when the payment failed or expired. Often nothing to do, but an
     * order has to release what it was holding.
     */
    public function onUnsuccessful(Payment $payment): void;
}
