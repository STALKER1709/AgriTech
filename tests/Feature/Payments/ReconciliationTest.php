<?php

declare(strict_types=1);

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Models\FarmerProfile;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentService;

/**
 * The safety net for the callback that never arrives: a queue worker that
 * died, a browser closed mid-flow, a delivery lost. Without this a farmer who
 * actually paid would stay blocked for ever.
 */
function stalePayment(int $minutesAgo = 30): Payment
{
    $farmer = User::factory()->awaitingPayment()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    $payment = Payment::factory()
        ->purpose(PaymentPurpose::RegistrationFee)
        ->for_($farmer)
        ->create(['user_id' => $farmer->id, 'amount' => 10_000]);

    $payment->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();

    return $payment->refresh();
}

it('expires a payment nobody ever answered', function () {
    $payment = stalePayment();

    $this->artisan('agritech:payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Expired);
    expect($payment->user->refresh()->status)->toBe(UserStatus::PendingPayment);
});

it('leaves a payment still inside its window alone', function () {
    $payment = stalePayment(minutesAgo: 1);

    $this->artisan('agritech:payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);
});

it('never touches a payment that already settled', function () {
    $succeeded = Payment::factory()->succeeded()->create();
    $failed = Payment::factory()->failed()->create();

    foreach ([$succeeded, $failed] as $payment) {
        $payment->forceFill(['created_at' => now()->subHour()])->save();
    }

    $this->artisan('agritech:payments:reconcile')->assertSuccessful();

    expect($succeeded->refresh()->status)->toBe(PaymentStatus::Succeeded);
    expect($failed->refresh()->status)->toBe(PaymentStatus::Failed);
});

it('says so when there is nothing to reconcile', function () {
    $this->artisan('agritech:payments:reconcile')
        ->expectsOutputToContain('Aucun paiement en attente')
        ->assertSuccessful();
});

it('honours an explicit window', function () {
    $payment = stalePayment(minutesAgo: 5);

    $this->artisan('agritech:payments:reconcile', ['--minutes' => 60])->assertSuccessful();
    expect($payment->refresh()->status)->toBe(PaymentStatus::Initiated);

    $this->artisan('agritech:payments:reconcile', ['--minutes' => 1])->assertSuccessful();
    expect($payment->refresh()->status)->toBe(PaymentStatus::Expired);
});

it('is safe to run twice', function () {
    $payment = stalePayment();

    $this->artisan('agritech:payments:reconcile')->assertSuccessful();
    $this->artisan('agritech:payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Expired);
});

it('reports a settled payment without changing it, when asked directly', function () {
    $payment = Payment::factory()->succeeded()->create();

    expect(app(PaymentService::class)->reconcile($payment))->toBe(PaymentStatus::Succeeded);
});
