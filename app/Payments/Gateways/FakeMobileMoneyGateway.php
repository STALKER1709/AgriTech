<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CallbackPayload;
use App\Payments\Data\GatewayRedirect;
use App\Payments\Exceptions\InvalidCallbackSignature;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A Mobile Money gateway that talks to nobody.
 *
 * It reproduces the whole cycle an operator puts a payment through — a hosted
 * page, a decision, a signed server-to-server callback after some latency, a
 * status endpoint to poll — so that every branch can be exercised locally,
 * offline, including the ones a real operator makes hard to trigger on demand:
 * a refusal, an expiry, a callback delivered twice.
 *
 * Nothing about it is more lenient than the real thing. Callbacks are signed
 * and verified exactly as they would be in production, because business rule
 * RG06 applies to the simulated gateway too.
 */
final class FakeMobileMoneyGateway implements PaymentGateway
{
    public const string PROVIDER = 'fake';

    public const string SIGNATURE_HEADER = 'X-AgriTech-Signature';

    public const string TIMESTAMP_HEADER = 'X-AgriTech-Timestamp';

    public function initiate(Payment $payment, PhoneNumber $payer): GatewayRedirect
    {
        // A real operator answers with its own reference here. Ours is already
        // on the payment, generated when it was created.
        return new GatewayRedirect(
            url: route('payments.sandbox', ['payment' => $payment->provider_reference]),
            providerReference: $payment->provider_reference,
        );
    }

    /**
     * Verify a callback: signature first, then age, then shape.
     *
     * Order matters. Nothing about the payload is trusted, not even enough to
     * be read, until the signature proves where it came from.
     */
    public function verifyCallback(Request $request): CallbackPayload
    {
        $signature = $request->header(self::SIGNATURE_HEADER);
        $timestamp = $request->header(self::TIMESTAMP_HEADER);

        if (! is_string($signature) || ! is_string($timestamp) || $timestamp === '') {
            throw InvalidCallbackSignature::missingHeaders();
        }

        $body = $request->getContent();

        if (! hash_equals(self::sign($timestamp, $body), $signature)) {
            throw InvalidCallbackSignature::mismatch();
        }

        $tolerance = (int) config('payments.webhook_tolerance_seconds', 300);
        $age = abs(now()->getTimestamp() - (int) $timestamp);

        if ($age > $tolerance) {
            throw InvalidCallbackSignature::tooOld($age, $tolerance);
        }

        return $this->parse($body);
    }

    public function getStatus(string $providerReference): PaymentStatus
    {
        $payment = Payment::query()
            ->where('provider_reference', $providerReference)
            ->first();

        if (! $payment instanceof Payment) {
            return PaymentStatus::Failed;
        }

        // The simulated operator holds no state of its own: what it knows is
        // what the sandbox page or the Artisan command decided. Deciding that
        // a payment has waited long enough is the application's call, not the
        // gateway's — a real operator has its own timeout and would not take
        // ours as an argument.
        return $payment->status;
    }

    public function refund(Payment $payment): bool
    {
        return $payment->isSuccessful();
    }

    /**
     * The signature a callback must carry.
     *
     * Signing the timestamp together with the body is what stops a captured
     * callback from being replayed later with a fresh timestamp.
     */
    public static function sign(string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, (string) config('payments.webhook_secret'));
    }

    public static function newEventId(): string
    {
        return 'evt_'.Str::lower((string) Str::ulid());
    }

    private function parse(string $body): CallbackPayload
    {
        /** @var mixed $decoded */
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw InvalidCallbackSignature::malformed('the body is not a JSON object.');
        }

        foreach (['event_id', 'reference', 'status', 'amount', 'currency', 'occurred_at'] as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw InvalidCallbackSignature::malformed(sprintf('[%s] is missing.', $key));
            }
        }

        $status = PaymentStatus::tryFrom((string) $decoded['status']);

        if ($status === null) {
            throw InvalidCallbackSignature::malformed(sprintf(
                '[%s] is not a payment status.',
                (string) $decoded['status'],
            ));
        }

        return new CallbackPayload(
            eventId: (string) $decoded['event_id'],
            providerReference: (string) $decoded['reference'],
            status: $status,
            amount: (int) $decoded['amount'],
            currency: (string) $decoded['currency'],
            occurredAt: CarbonImmutable::parse((string) $decoded['occurred_at']),
            raw: $decoded,
        );
    }
}
