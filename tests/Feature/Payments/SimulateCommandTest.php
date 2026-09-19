<?php

declare(strict_types=1);

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\FarmerProfile;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\User;

function commandPayment(): Payment
{
    $farmer = User::factory()->awaitingPayment()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    return Payment::factory()
        ->purpose(PaymentPurpose::RegistrationFee)
        ->for_($farmer)
        ->create(['user_id' => $farmer->id, 'amount' => 10_000]);
}

it('forces a payment to succeed from the terminal', function () {
    User::factory()->admin()->create();
    $payment = commandPayment();

    $this->artisan('agritech:payment:simulate', [
        'reference' => $payment->provider_reference,
        'outcome' => 'succeeded',
        '--now' => true,
    ])->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);
    expect($payment->user->refresh()->status)->toBe(UserStatus::PendingValidation);
});

it('forces a refusal', function () {
    $payment = commandPayment();

    $this->artisan('agritech:payment:simulate', [
        'reference' => $payment->provider_reference,
        'outcome' => 'failed',
        '--now' => true,
    ])->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
});

it('sends the same callback twice without doubling its effect', function () {
    User::factory()->admin()->create();
    $payment = commandPayment();

    $this->artisan('agritech:payment:simulate', [
        'reference' => $payment->provider_reference,
        'outcome' => 'succeeded',
        '--duplicate' => true,
        '--now' => true,
    ])->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Succeeded);

    // Two deliveries, one stored callback: the unique event id absorbed the
    // replay, which is exactly what business rule RG06 asks for.
    expect(PaymentCallback::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('sends nothing for an expiry and says why', function () {
    $payment = commandPayment();

    $this->artisan('agritech:payment:simulate', [
        'reference' => $payment->provider_reference,
        'outcome' => 'expired',
    ])
        ->expectsOutputToContain('Aucun callback envoyé')
        ->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
    expect(PaymentCallback::query()->count())->toBe(0);
});

it('refuses an unknown reference', function () {
    $this->artisan('agritech:payment:simulate', [
        'reference' => 'PAY-INEXISTANTE',
        'outcome' => 'succeeded',
    ])->assertFailed();
});

it('refuses an outcome that is not one of the three', function () {
    $payment = commandPayment();

    $this->artisan('agritech:payment:simulate', [
        'reference' => $payment->provider_reference,
        'outcome' => 'refunded',
    ])->assertFailed();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});
