<?php

declare(strict_types=1);

namespace App\Exceptions;

use BackedEnum;
use DomainException;

/**
 * Raised when code attempts a status change the domain does not allow, for
 * instance paying an order that was already cancelled.
 */
final class InvalidStatusTransition extends DomainException
{
    public static function between(string $subject, BackedEnum $from, BackedEnum $to): self
    {
        return new self(sprintf(
            'Cannot move %s from [%s] to [%s].',
            $subject,
            (string) $from->value,
            (string) $to->value,
        ));
    }
}
