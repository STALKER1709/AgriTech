<?php

declare(strict_types=1);

namespace App\Payments\Data;

/**
 * Where to send the payer once a payment has been initiated.
 *
 * With a real operator this would be the checkout URL they host. With the
 * simulated gateway it is an internal page, which is exactly why the rest of
 * the application only ever sees this object and not the gateway's internals.
 *
 * @immutable
 */
final readonly class GatewayRedirect
{
    public function __construct(
        public string $url,
        public string $providerReference,
    ) {}
}
