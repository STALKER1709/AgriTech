<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CallbackPayload;
use App\Payments\Data\GatewayRedirect;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use App\Payments\Outcomes\OutcomeRegistry;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Drives a payment from initiation to its business effect.
 *
 * Two rules shape everything here. The amount is recomputed server-side and
 * never read from a form (an amount in a request is a suggestion, not a fact).
 * And a payment only reaches Succeeded through a verified server-side
 * confirmation — business rule RG06 — which in practice means this class is
 * the only one allowed to call markAsSucceeded, and only from applyCallback()
 * or reconcile().
 */
final class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OutcomeRegistry $outcomes,
    ) {}

    /**
     * Create a payment and hand back where to send the payer.
     *
     * The caller passes what is being paid for, not how much: the amount comes
     * from the server's own reading of the situation.
     */
    public function initiate(
        User $payer,
        Money $amount,
        PaymentPurpose $purpose,
        PaymentMethod $method,
        PhoneNumber $payerNumber,
        ?Model $payable = null,
    ): GatewayRedirect {
        $payment = Payment::create([
            'user_id' => $payer->id,
            'amount' => $amount,
            'currency' => Money::CURRENCY,
            'purpose' => $purpose,
            'payable_type' => $payable?->getMorphClass(),
            'payable_id' => $payable?->getKey(),
            'provider' => FakeMobileMoneyGateway::PROVIDER,
            'provider_reference' => self::newProviderReference(),
            'method' => $method,
            'status' => PaymentStatus::Initiated,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        return $this->gateway->initiate($payment, $payerNumber);
    }

    /**
     * Record and apply a callback that has already been verified.
     *
     * Idempotence is the database's job: the callback row is inserted first,
     * and a duplicate event_id collides on the unique index. Checking for an
     * existing row in PHP instead would leave a window where two concurrent
     * deliveries both find nothing and both apply the effect.
     *
     * @return bool true when this callback did the work, false when it was a
     *              replay and nothing was applied a second time
     */
    public function applyCallback(CallbackPayload $payload, string $signature): bool
    {
        $payment = Payment::query()
            ->where('provider_reference', $payload->providerReference)
            ->firstOrFail();

        try {
            $callback = PaymentCallback::create([
                'payment_id' => $payment->id,
                'event_id' => $payload->eventId,
                'signature' => $signature,
                'payload' => $payload->raw,
                'received_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKey($exception)) {
                throw $exception;
            }

            Log::info('Callback de paiement ignoré : déjà traité.', [
                'event_id' => $payload->eventId,
                'reference' => $payload->providerReference,
            ]);

            return false;
        }

        // The callback states an amount. It is compared, never trusted: a
        // mismatch means something is wrong upstream, and the safe answer is
        // to leave the payment alone rather than take the caller's word.
        if ($payload->amount !== $payment->amount->amount) {
            Log::warning('Callback de paiement refusé : le montant ne correspond pas.', [
                'reference' => $payment->provider_reference,
                'attendu' => $payment->amount->amount,
                'reçu' => $payload->amount,
            ]);

            $callback->markProcessed();

            return false;
        }

        $this->transitionAndApply($payment, $payload->status);

        $callback->markProcessed();

        return true;
    }

    /**
     * Ask the gateway where a stale payment stands, and apply what it says.
     *
     * This is the second server-side route business rule RG06 allows, and the
     * safety net for the callback that never arrives.
     */
    public function reconcile(Payment $payment, ?int $expireAfterMinutes = null): PaymentStatus
    {
        if (! $payment->status->isAwaitingOutcome()) {
            return $payment->status;
        }

        $status = $this->gateway->getStatus($payment->provider_reference);

        // The gateway reports; the application decides. When it still has no
        // answer and the payment has waited longer than we are willing to,
        // the payment is expired here rather than left hanging for ever.
        if ($status->isAwaitingOutcome()) {
            $window = $expireAfterMinutes ?? (int) config('payments.expiration_minutes', 15);

            if (! $this->hasWaitedLongerThan($payment, $window)) {
                return $payment->status;
            }

            $status = PaymentStatus::Expired;
        }

        if ($status === $payment->status) {
            return $payment->status;
        }

        $this->transitionAndApply($payment, $status);

        return $payment->refresh()->status;
    }

    private function hasWaitedLongerThan(Payment $payment, int $minutes): bool
    {
        return $payment->created_at !== null
            && $payment->created_at->addMinutes($minutes)->isPast();
    }

    /**
     * Move the payment, then run the business effect, all or nothing.
     */
    private function transitionAndApply(Payment $payment, PaymentStatus $status): void
    {
        DB::transaction(function () use ($payment, $status): void {
            $fresh = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo($status)) {
                Log::info('Transition de paiement ignorée.', [
                    'reference' => $fresh->provider_reference,
                    'de' => $fresh->status->value,
                    'vers' => $status->value,
                ]);

                return;
            }

            match ($status) {
                PaymentStatus::Succeeded => $fresh->markAsSucceeded(),
                PaymentStatus::Failed => $fresh->markAsFailed(),
                PaymentStatus::Expired => $fresh->markAsExpired(),
                PaymentStatus::Pending => $fresh->markAsPending(),
                default => null,
            };

            if ($status === PaymentStatus::Pending) {
                return;
            }

            $outcome = $this->outcomes->for($fresh->purpose);

            $status === PaymentStatus::Succeeded
                ? $outcome->onSucceeded($fresh)
                : $outcome->onUnsuccessful($fresh);

            $payment->setRawAttributes($fresh->getAttributes(), sync: true);
        });
    }

    public static function newProviderReference(): string
    {
        return 'PAY-'.Str::upper(Str::random(16));
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        // 23000 covers the integrity constraint violations of MySQL, MariaDB,
        // PostgreSQL and SQLite alike.
        return $exception->getCode() === '23000';
    }
}
