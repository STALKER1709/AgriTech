<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'En attente de paiement',
            self::Active => 'Actif',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Active, self::Cancelled],
            self::Active => [self::Expired, self::Cancelled],
            self::Expired => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    public function grantsAccess(): bool
    {
        return $this === self::Active;
    }
}
