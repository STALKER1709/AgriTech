<?php

declare(strict_types=1);

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\FarmerProfile;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\User;
use App\Notifications\FarmerAwaitingValidation;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * Business rule RG06 under every angle it can be attacked from.
 */
function callbackBody(Payment $payment, PaymentStatus $status, ?string $eventId = null, ?int $amount = null): string
{
    return json_encode([
        'event_id' => $eventId ?? FakeMobileMoneyGateway::newEventId(),
        'reference' => $payment->provider_reference,
        'status' => $status->value,
        'amount' => $amount ?? $payment->amount->amount,
        'currency' => $payment->currency,
        'method' => $payment->method->value,
        'occurred_at' => now()->toIso8601String(),
    ], JSON_THROW_ON_ERROR);
}

function postCallback(string $body, ?string $signature = null, ?string $timestamp = null): TestResponse
{
    $timestamp ??= (string) now()->getTimestamp();

    return test()->call(
        method: 'POST',
        uri: route('webhooks.payment'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_AGRITECH_SIGNATURE' => $signature ?? FakeMobileMoneyGateway::sign($timestamp, $body),
            'HTTP_X_AGRITECH_TIMESTAMP' => $timestamp,
        ],
        content: $body,
    );
}

function pendingFarmerPayment(): Payment
{
    $farmer = User::factory()->awaitingPayment()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    return Payment::factory()
        ->purpose(PaymentPurpose::RegistrationFee)
        ->for_($farmer)
        ->create(['user_id' => $farmer->id, 'amount' => 10_000]);
}

describe('signature', function () {
    it('refuses a callback with no signature at all', function () {
        $payment = pendingFarmerPayment();

        $this->postJson(route('webhooks.payment'), json_decode(callbackBody($payment, PaymentStatus::Succeeded), true))
            ->assertForbidden();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    });

    it('refuses a forged signature and changes nothing', function () {
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Succeeded), signature: str_repeat('a', 64))
            ->assertForbidden();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingPayment);
        expect(PaymentCallback::query()->count())->toBe(0);
    });

    it('refuses a payload altered after signing', function () {
        $payment = pendingFarmerPayment();
        $body = callbackBody($payment, PaymentStatus::Succeeded);
        $timestamp = (string) now()->getTimestamp();
        $signature = FakeMobileMoneyGateway::sign($timestamp, $body);

        $tampered = str_replace('"amount":10000', '"amount":1', $body);

        postCallback($tampered, $signature, $timestamp)->assertForbidden();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    });

    it('refuses a callback replayed long after it was signed', function () {
        $payment = pendingFarmerPayment();
        $body = callbackBody($payment, PaymentStatus::Succeeded);
        $stale = (string) now()->subHour()->getTimestamp();

        postCallback($body, FakeMobileMoneyGateway::sign($stale, $body), $stale)
            ->assertForbidden();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    });
});

describe('outcomes', function () {
    it('confirms the payment and sends the farmer to review, per business rule RG02', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Succeeded))
            ->assertOk()
            ->assertJson(['applied' => true]);

        expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
        expect($payment->confirmed_at)->not->toBeNull();
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingValidation);

        Notification::assertSentTo($admin, FarmerAwaitingValidation::class);
    });

    it('records a refusal and leaves the farmer awaiting payment', function () {
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Failed))->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingPayment);
    });

    it('records an expiry and leaves the farmer awaiting payment', function () {
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Expired))->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Expired);
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingPayment);
    });

    it('keeps the raw payload for auditing', function () {
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Succeeded))->assertOk();

        $callback = PaymentCallback::query()->sole();

        expect($callback->payment_id)->toBe($payment->id);
        expect($callback->payload['reference'])->toBe($payment->provider_reference);
        expect($callback->wasProcessed())->toBeTrue();
    });
});

describe('idempotence', function () {
    it('applies the same callback only once', function () {
        Notification::fake();

        User::factory()->admin()->create();
        $payment = pendingFarmerPayment();
        $body = callbackBody($payment, PaymentStatus::Succeeded);

        postCallback($body)->assertOk()->assertJson(['applied' => true]);
        $confirmedAt = $payment->refresh()->confirmed_at;

        // The very same callback, delivered a second time.
        postCallback($body)->assertOk()->assertJson(['applied' => false]);

        expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
        expect($payment->confirmed_at->equalTo($confirmedAt))->toBeTrue();
        expect(PaymentCallback::query()->count())->toBe(1);

        Notification::assertCount(1);
    });

    it('does not let a late refusal undo a confirmed payment', function () {
        Notification::fake();
        User::factory()->admin()->create();

        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Succeeded))->assertOk();
        postCallback(callbackBody($payment, PaymentStatus::Failed))->assertOk();

        expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingValidation);
    });
});

describe('amount', function () {
    it('refuses to act on a callback whose amount does not match', function () {
        $payment = pendingFarmerPayment();

        postCallback(callbackBody($payment, PaymentStatus::Succeeded, amount: 1))
            ->assertOk()
            ->assertJson(['applied' => false]);

        expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
        expect($payment->user->refresh()->status)->toBe(UserStatus::PendingPayment);
    });
});

it('answers politely to a callback for an unknown payment', function () {
    $payment = pendingFarmerPayment();
    $body = str_replace($payment->provider_reference, 'PAY-INCONNUE00000000', callbackBody($payment, PaymentStatus::Succeeded));

    postCallback($body)->assertOk();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});
