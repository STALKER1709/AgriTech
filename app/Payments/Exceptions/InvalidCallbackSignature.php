<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

use RuntimeException;

/**
 * Raised when a callback cannot be trusted: wrong signature, missing header,
 * or a timestamp outside the accepted window.
 *
 * Nothing happens to the payment when this is thrown. Business rule RG06 makes
 * a verified confirmation the only thing that may move a payment forward.
 */
final class InvalidCallbackSignature extends RuntimeException
{
    public static function missingHeaders(): self
    {
        return new self('The callback carries no signature or no timestamp.');
    }

    public static function mismatch(): self
    {
        return new self('The callback signature does not match its payload.');
    }

    public static function tooOld(int $ageInSeconds, int $tolerance): self
    {
        return new self(sprintf(
            'The callback is %d seconds old, beyond the %d second window.',
            $ageInSeconds,
            $tolerance,
        ));
    }

    public static function malformed(string $detail): self
    {
        return new self('The callback payload is malformed: '.$detail);
    }
}
