<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\UserStatus;
use App\Models\Setting;
use App\Models\User;
use App\Payments\Data\GatewayRedirect;
use App\Support\Money;
use App\Support\PhoneNumber;
use DomainException;

/**
 * Starts the farmer registration fee payment.
 *
 * The amount is read from the settings table, never from the request. A form
 * can say anything; what a farmer owes is the platform's own business.
 */
final class RegistrationFeeService
{
    public function __construct(private readonly PaymentService $payments) {}

    public function amountDue(): Money
    {
        $amount = Setting::query()
            ->where('key', Setting::FARMER_REGISTRATION_FEE)
            ->first()?->integerValue() ?? 0;

        return Money::fromInteger($amount);
    }

    public function start(User $farmer, PaymentMethod $method, PhoneNumber $payerNumber): GatewayRedirect
    {
        if ($farmer->status !== UserStatus::PendingPayment) {
            throw new DomainException(
                'Only an account awaiting payment can start the registration fee.',
            );
        }

        $amount = $this->amountDue();

        if (! $amount->isPositive()) {
            throw new DomainException('The registration fee is not configured.');
        }

        return $this->payments->initiate(
            payer: $farmer,
            amount: $amount,
            purpose: PaymentPurpose::RegistrationFee,
            method: $method,
            payerNumber: $payerNumber,
            payable: $farmer,
        );
    }
}
