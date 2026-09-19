<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\Exceptions\PaymentOutcomeNotHandled;
use App\Services\Orders\OrderFulfilmentService;

/**
 * What a paid order does: move the stock and hand the work to the farmers.
 *
 * A thin adapter on purpose — the rules themselves belong to the service, so
 * they can be exercised without going through a payment callback.
 */
final class OrderOutcome implements HandlesPaymentOutcome
{
    public function __construct(private readonly OrderFulfilmentService $fulfilment) {}

    public function onSucceeded(Payment $payment): void
    {
        $this->fulfilment->applyPaymentSuccess($payment, $this->order($payment));
    }

    public function onUnsuccessful(Payment $payment): void
    {
        $this->fulfilment->applyPaymentFailure($payment, $this->order($payment));
    }

    /**
     * A payment whose purpose is Order but which points at nothing — or at
     * something else — is a bug, not a case to absorb quietly.
     */
    private function order(Payment $payment): Order
    {
        $payable = $payment->payable;

        if (! $payable instanceof Order) {
            throw PaymentOutcomeNotHandled::forPurpose($payment->purpose);
        }

        return $payable->load('subOrders');
    }
}
