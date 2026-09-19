<?php

declare(strict_types=1);

namespace App\Payments\Data;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

/**
 * A verified callback from the gateway.
 *
 * Only produced by PaymentGateway::verifyCallback(), and only once the
 * signature and the timestamp have checked out. Holding a value of this type
 * means the callback is trustworthy — business rule RG06 hangs on that.
 *
 * @immutable
 */
final readonly class CallbackPayload
{
    /**
     * @param  array<string, mixed>  $raw  the payload exactly as received
     */
    public function __construct(
        public string $eventId,
        public string $providerReference,
        public PaymentStatus $status,
        public int $amount,
        public string $currency,
        public CarbonImmutable $occurredAt,
        public array $raw,
    ) {}
}
