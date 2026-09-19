<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initié',
            self::Pending => 'En cours',
            self::Succeeded => 'Réussi',
            self::Failed => 'Échoué',
            self::Expired => 'Expiré',
            self::Refunded => 'Remboursé',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Initiated => [self::Pending, self::Succeeded, self::Failed, self::Expired],
            self::Pending => [self::Succeeded, self::Failed, self::Expired],
            self::Succeeded => [self::Refunded],
            self::Failed => [],
            self::Expired => [],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    /**
     * A payment still awaiting an answer from the gateway is the one the
     * reconciliation task has to chase.
     */
    public function isAwaitingOutcome(): bool
    {
        return $this === self::Initiated || $this === self::Pending;
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
