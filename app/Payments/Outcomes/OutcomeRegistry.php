<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Enums\PaymentPurpose;
use App\Payments\Exceptions\PaymentOutcomeNotHandled;
use Illuminate\Contracts\Container\Container;

/**
 * Finds the business effect for a payment's purpose.
 *
 * Purposes whose phase has not arrived are absent on purpose. Asking for one
 * throws rather than doing nothing quietly: a client paying for an order and
 * seeing nothing happen is a far worse failure than a loud exception during
 * development.
 */
final class OutcomeRegistry
{
    /**
     * @var array<string, class-string<HandlesPaymentOutcome>>
     */
    private const array HANDLERS = [
        PaymentPurpose::RegistrationFee->value => RegistrationFeeOutcome::class,
        // PaymentPurpose::Order          — phase 6
        // PaymentPurpose::Training       — phase 7
        // PaymentPurpose::Subscription   — phase 8
    ];

    public function __construct(private readonly Container $container) {}

    public function for(PaymentPurpose $purpose): HandlesPaymentOutcome
    {
        $handler = self::HANDLERS[$purpose->value] ?? null;

        if ($handler === null) {
            throw PaymentOutcomeNotHandled::forPurpose($purpose);
        }

        /** @var HandlesPaymentOutcome */
        return $this->container->make($handler);
    }

    public function handles(PaymentPurpose $purpose): bool
    {
        return isset(self::HANDLERS[$purpose->value]);
    }
}
