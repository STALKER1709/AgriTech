<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Enums\PaymentPurpose;
use Illuminate\Contracts\Container\Container;

/**
 * Finds the business effect for a payment's purpose.
 *
 * The mapping is an exhaustive match on purpose: adding a new PaymentPurpose
 * without its handler is a TypeError here, at the first test run, rather than
 * a silent no-op discovered by a client whose payment "went through" and did
 * nothing. Every purpose has exactly one handler.
 */
final class OutcomeRegistry
{
    public function __construct(private readonly Container $container) {}

    public function for(PaymentPurpose $purpose): HandlesPaymentOutcome
    {
        $handler = match ($purpose) {
            PaymentPurpose::RegistrationFee => RegistrationFeeOutcome::class,
            PaymentPurpose::Order => OrderOutcome::class,
            PaymentPurpose::Training => TrainingOutcome::class,
            PaymentPurpose::Subscription => SubscriptionOutcome::class,
        };

        /** @var HandlesPaymentOutcome */
        return $this->container->make($handler);
    }
}
