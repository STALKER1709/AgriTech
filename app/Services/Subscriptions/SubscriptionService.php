<?php

declare(strict_types=1);

namespace App\Services\Subscriptions;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\GatewayRedirect;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use App\Support\PhoneNumber;
use DomainException;

/**
 * Subscribes a client to a plan.
 *
 * A plan's price is what the server charges, never what a form says. A client
 * who presses the button twice is sent back to the payment already in flight
 * rather than handed a second one.
 *
 * Two subscriptions never run at once. When a term is still under way, a new
 * payment is refused: the plan already paid for is the one in force, and
 * stacking a second one on top would invent a prorated refund nobody asked
 * for — the same reasoning that keeps a partially delivered order out of the
 * domain. Renewing is simply buying again once the current term has lapsed.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {}

    public function start(SubscriptionPlan $plan, User $client, PaymentMethod $method, PhoneNumber $payerNumber): GatewayRedirect
    {
        if (! $plan->is_active) {
            throw new DomainException('Ce plan d\'abonnement n\'est plus proposé.');
        }

        if ($this->runningSubscription($client) instanceof Subscription) {
            throw new DomainException('Votre abonnement actuel est toujours en cours. Attendez son terme pour changer de plan.');
        }

        $inFlight = $this->paymentInFlight($client);

        if ($inFlight instanceof Payment) {
            return $this->gateway->initiate($inFlight, $payerNumber);
        }

        $subscription = Subscription::create([
            'client_id' => $client->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PendingPayment,
        ]);

        return $this->payments->initiate(
            payer: $client,
            amount: Money::fromInteger($plan->price->amount),
            purpose: PaymentPurpose::Subscription,
            method: $method,
            payerNumber: $payerNumber,
            payable: $subscription,
        );
    }

    /**
     * The subscription whose term is still running, if any.
     */
    public function runningSubscription(User $client): ?Subscription
    {
        return $client->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();
    }

    /**
     * The payment already started and still awaiting an answer, whichever
     * plan it was for: two live subscription payments is two chances to pay.
     */
    public function paymentInFlight(User $client): ?Payment
    {
        return $client->payments()
            ->where('purpose', PaymentPurpose::Subscription)
            ->awaitingOutcome()
            ->latest('id')
            ->first();
    }
}
