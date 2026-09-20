<?php

declare(strict_types=1);

namespace App\Services\Trainings;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Models\Training;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\GatewayRedirect;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use App\Support\PhoneNumber;
use DomainException;

/**
 * Starts the purchase of a training.
 *
 * The amount is the training's own price, read from the server's record and
 * never from a form. A client who presses the button twice is sent back to the
 * payment already in flight rather than handed a second one — and the unique
 * constraint on training_purchases backs the rule at the database level, so
 * even two payments that both succeed cannot produce two purchase rows.
 */
final class TrainingPaymentService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {}

    public function start(Training $training, User $client, PaymentMethod $method, PhoneNumber $payerNumber): GatewayRedirect
    {
        if (! $training->status->isVisibleToPublic()) {
            throw new DomainException('Cette formation n\'est pas disponible à l\'achat.');
        }

        if ($training->isAccessibleBy($client)) {
            throw new DomainException('Vous avez déjà accès à cette formation.');
        }

        $inFlight = $this->paymentInFlight($training, $client);

        if ($inFlight instanceof Payment) {
            return $this->gateway->initiate($inFlight, $payerNumber);
        }

        return $this->payments->initiate(
            payer: $client,
            amount: Money::fromInteger($training->price->amount),
            purpose: PaymentPurpose::Training,
            method: $method,
            payerNumber: $payerNumber,
            payable: $training,
        );
    }

    /**
     * The payment already started for this training by this client and still
     * awaiting an answer, if there is one.
     */
    public function paymentInFlight(Training $training, User $client): ?Payment
    {
        return $training->payments()
            ->where('user_id', $client->id)
            ->awaitingOutcome()
            ->latest('id')
            ->first();
    }
}
