<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

use App\Enums\PaymentPurpose;
use RuntimeException;

/**
 * Raised when a payment succeeds for a purpose whose business effect has not
 * been built yet.
 *
 * Deliberately loud. A silent no-op here would mean a client pays for an order
 * and nothing happens, discovered much later; an exception makes the gap
 * impossible to miss the moment the matching phase starts.
 */
final class PaymentOutcomeNotHandled extends RuntimeException
{
    public static function forPurpose(PaymentPurpose $purpose): self
    {
        return new self(sprintf(
            'No business effect is wired up for payments of purpose [%s] yet.',
            $purpose->value,
        ));
    }
}
