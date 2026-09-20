<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Models\Payment;
use App\Models\Training;
use App\Payments\Exceptions\PaymentOutcomeNotHandled;
use App\Services\Trainings\TrainingAccessService;

/**
 * What a paid training does: record the purchase and open the content.
 *
 * A thin adapter on purpose — the rules live in the service, so they can be
 * exercised without going through a payment callback. The purchase row is
 * created inside the transaction that confirmed the payment, exactly like the
 * stock movement of an order.
 */
final class TrainingOutcome implements HandlesPaymentOutcome
{
    public function __construct(private readonly TrainingAccessService $access) {}

    public function onSucceeded(Payment $payment): void
    {
        $this->access->recordPurchase(
            $this->training($payment),
            $payment->user,
        );
    }

    public function onUnsuccessful(Payment $payment): void
    {
        // Nothing to release: no stock was ever held for a training, and the
        // purchase row only exists once the payment has gone through.
    }

    /**
     * A payment whose purpose is Training but which points at nothing — or at
     * something else — is a bug, not a case to absorb quietly.
     */
    private function training(Payment $payment): Training
    {
        $payable = $payment->payable;

        if (! $payable instanceof Training) {
            throw PaymentOutcomeNotHandled::forPurpose($payment->purpose);
        }

        return $payable;
    }
}
