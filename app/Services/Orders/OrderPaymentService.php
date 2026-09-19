<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\GatewayRedirect;
use App\Services\Payments\PaymentService;
use App\Support\PhoneNumber;
use DomainException;

/**
 * Starts the payment of an order.
 *
 * The amount is the order's own total, recomputed nowhere and read from no
 * form. And a client who presses the button twice is sent back to the payment
 * already in flight rather than given a second one: two live payments for one
 * order is how a client ends up paying twice.
 */
final class OrderPaymentService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {}

    public function start(Order $order, PaymentMethod $method, PhoneNumber $payerNumber): GatewayRedirect
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            throw new DomainException('Cette commande n\'attend plus de paiement.');
        }

        if ($order->hasExpired()) {
            throw new DomainException('Le délai de paiement de cette commande est dépassé.');
        }

        $inFlight = $this->paymentInFlight($order);

        if ($inFlight instanceof Payment) {
            return $this->gateway->initiate($inFlight, $payerNumber);
        }

        return $this->payments->initiate(
            payer: $order->client,
            amount: $order->total_amount,
            purpose: PaymentPurpose::Order,
            method: $method,
            payerNumber: $payerNumber,
            payable: $order,
        );
    }

    /**
     * The payment already started for this order and still awaiting an
     * answer, if there is one.
     */
    public function paymentInFlight(Order $order): ?Payment
    {
        return $order->payments()
            ->awaitingOutcome()
            ->latest('id')
            ->first();
    }
}
