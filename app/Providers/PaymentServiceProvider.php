<?php

declare(strict_types=1);

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * Binds the payment gateway named in the configuration.
 *
 * Only `fake` ships with the project. An unknown name fails loudly at boot
 * rather than silently falling back to the simulated gateway, which is the
 * kind of default that would one day take fake payments for real ones.
 */
final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $gateway = (string) config('payments.gateway');

            return match ($gateway) {
                FakeMobileMoneyGateway::PROVIDER => new FakeMobileMoneyGateway,
                default => throw new RuntimeException(sprintf(
                    'Unknown payment gateway [%s]. Only [%s] is available.',
                    $gateway,
                    FakeMobileMoneyGateway::PROVIDER,
                )),
            };
        });
    }
}
