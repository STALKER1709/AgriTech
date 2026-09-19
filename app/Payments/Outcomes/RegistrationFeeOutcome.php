<?php

declare(strict_types=1);

namespace App\Payments\Outcomes;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\FarmerAwaitingValidation;
use Illuminate\Support\Facades\Notification;

/**
 * What a paid registration fee does: move the farmer on to review.
 *
 * Business rule RG02 — a farmer account only reaches PendingValidation after a
 * successful fee payment — is enforced here and nowhere else.
 */
final class RegistrationFeeOutcome implements HandlesPaymentOutcome
{
    public function onSucceeded(Payment $payment): void
    {
        $farmer = $payment->user;

        // Reconciliation and a late callback can both land on an account that
        // already moved on. That is not an error, it is the same outcome
        // arriving twice, so there is nothing left to do.
        if ($farmer->status !== UserStatus::PendingPayment) {
            return;
        }

        $farmer->markAwaitingValidation();

        $admins = User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->get();

        Notification::send($admins, new FarmerAwaitingValidation($farmer));
    }

    public function onUnsuccessful(Payment $payment): void
    {
        // The account simply stays where it is: awaiting payment. The farmer
        // can try again from their status screen.
    }
}
