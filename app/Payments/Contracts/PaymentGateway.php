<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Data\CallbackPayload;
use App\Payments\Data\GatewayRedirect;
use App\Payments\Exceptions\InvalidCallbackSignature;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

/**
 * The seam between AgriTech and a Mobile Money operator.
 *
 * Only one implementation ships with the project — FakeMobileMoneyGateway,
 * which reproduces the whole cycle locally. Nothing outside app/Payments knows
 * which implementation is in use, so plugging in a real operator later means
 * writing one class and changing one environment variable.
 */
interface PaymentGateway
{
    /**
     * Start a payment and say where to send the payer.
     *
     * The amount comes from the Payment, which the service recomputed
     * server-side; nothing here ever reads it from a form.
     */
    public function initiate(Payment $payment, PhoneNumber $payer): GatewayRedirect;

    /**
     * Check a callback and turn it into a trustworthy value.
     *
     * @throws InvalidCallbackSignature when the signature or the timestamp
     *                                  does not check out
     */
    public function verifyCallback(Request $request): CallbackPayload;

    /**
     * Ask the gateway where a payment stands.
     *
     * This is what the reconciliation task uses when no callback ever arrived,
     * and it is the second server-side route to a confirmed payment allowed by
     * business rule RG06.
     */
    public function getStatus(string $providerReference): PaymentStatus;

    /**
     * Refund a successful payment. Optional: an operator may not support it.
     */
    public function refund(Payment $payment): bool;
}
