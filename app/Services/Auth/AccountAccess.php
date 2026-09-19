<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;

/**
 * Decides whether an account may open a session at all.
 *
 * A farmer awaiting payment or validation must be able to log in — that is how
 * they pay the fee and follow their file. A suspended, rejected or deleted
 * account must not, and is told why: answering "wrong credentials" to someone
 * whose credentials are correct sends them round in circles.
 */
final class AccountAccess
{
    public function canSignIn(User $user): bool
    {
        return $this->refusalReason($user) === null;
    }

    /**
     * The translation key explaining the refusal, or null when access is fine.
     */
    public function refusalReason(User $user): ?string
    {
        return match ($user->status) {
            UserStatus::Suspended => 'auth.suspended',
            UserStatus::Rejected => 'auth.rejected',
            UserStatus::Deleted => 'auth.deleted',
            UserStatus::Active,
            UserStatus::PendingPayment,
            UserStatus::PendingValidation => null,
        };
    }
}
