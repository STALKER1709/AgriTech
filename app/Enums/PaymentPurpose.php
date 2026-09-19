<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentPurpose: string
{
    case Order = 'order';
    case Training = 'training';
    case Subscription = 'subscription';
    case RegistrationFee = 'registration_fee';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Commande',
            self::Training => 'Formation',
            self::Subscription => 'Abonnement',
            self::RegistrationFee => "Frais d'inscription",
        };
    }
}
