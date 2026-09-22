<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Picks the sidebar navigation partial for the signed-in user.
 *
 * One shell, four navigations. Duplicating the whole layout per role would
 * mean fixing every ergonomic detail four times; the navigation is the only
 * part that genuinely differs.
 */
final class RoleNavigation
{
    public static function partialFor(?User $user): string
    {
        if (! $user instanceof User) {
            return 'pending';
        }

        if (! $user->isActive()) {
            return 'pending';
        }

        return match ($user->role) {
            UserRole::Client => 'client',
            UserRole::Farmer => 'farmer',
            UserRole::Admin => 'admin',
        };
    }

    /**
     * The line under the brand in the sidebar, as the mockups word it
     * ("Back-office Central" on the admin screens).
     */
    public static function spaceLabel(?User $user): string
    {
        if (! $user instanceof User || ! $user->isActive()) {
            return __('Mon compte');
        }

        return match ($user->role) {
            UserRole::Client => __('Espace client'),
            UserRole::Farmer => __('Espace vendeur'),
            UserRole::Admin => __('Back-office Central'),
        };
    }
}
