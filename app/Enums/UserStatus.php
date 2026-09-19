<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingValidation = 'pending_validation';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'En attente de paiement',
            self::PendingValidation => 'En attente de validation',
            self::Active => 'Actif',
            self::Suspended => 'Suspendu',
            self::Rejected => 'Refusé',
            self::Deleted => 'Supprimé',
        };
    }

    /**
     * A deleted account is terminal: business rule RG08 keeps the row for
     * order and payment history, so it must never come back to life.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::PendingValidation, self::Deleted],
            self::PendingValidation => [self::Active, self::Rejected, self::Deleted],
            self::Active => [self::Suspended, self::Deleted],
            self::Suspended => [self::Active, self::Deleted],
            self::Rejected => [self::PendingValidation, self::Deleted],
            self::Deleted => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }
}
