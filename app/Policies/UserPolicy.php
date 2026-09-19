<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Privilege;
use App\Models\User;

/**
 * Who may act on an account, per business rule RG07.
 *
 * The admin role opens the door to the administration area; it authorises
 * nothing on its own. Every decision below is keyed on a privilege the
 * administrator actually holds.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Approving or rejecting only makes sense on an account that paid its fee
     * and is waiting, which is what business rule RG02 left behind.
     */
    public function decide(User $actor, User $target): bool
    {
        return $actor->hasPrivilege(Privilege::APPROVE_FARMERS)
            && $target->role === UserRole::Farmer
            && $target->status === UserStatus::PendingValidation;
    }

    public function suspend(User $actor, User $target): bool
    {
        return $actor->hasPrivilege(Privilege::SUSPEND_USERS)
            && ! $actor->is($target)
            && $target->status === UserStatus::Active;
    }

    public function reinstate(User $actor, User $target): bool
    {
        return $actor->hasPrivilege(Privilege::SUSPEND_USERS)
            && $target->status === UserStatus::Suspended;
    }

    /**
     * Deleting an account is irreversible by design — business rule RG08 makes
     * Deleted a terminal status — so two things are guarded here: an
     * administrator cannot delete themselves, and the last active
     * administrator cannot be deleted at all. Either would leave the platform
     * with nobody able to run it.
     */
    public function delete(User $actor, User $target): bool
    {
        if (! $actor->hasPrivilege(Privilege::DELETE_USERS)) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        if ($target->status === UserStatus::Deleted) {
            return false;
        }

        if ($target->isAdmin() && $this->isLastActiveAdmin($target)) {
            return false;
        }

        return true;
    }

    /**
     * Changing privileges is what would let an administrator lock everyone
     * else out, so it is also guarded against self-editing.
     */
    public function managePrivileges(User $actor, User $target): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_PRIVILEGES)
            && $target->isAdmin()
            && ! $actor->is($target);
    }

    private function isLastActiveAdmin(User $target): bool
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->whereKeyNot($target->id)
            ->doesntExist();
    }
}
