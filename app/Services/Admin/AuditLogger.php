<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes the administrative audit trail required by business rule RG11.
 *
 * Deliberately called by hand rather than hung off model observers. An
 * observer would record everything, including the technical writes a queued
 * job makes to a timestamp, and would bury the administrative actions in
 * noise. Naming each sensitive action is both more accurate and easier to
 * read back months later.
 */
final class AuditLogger
{
    public const string FARMER_APPROVED = 'farmer.approved';

    public const string FARMER_REJECTED = 'farmer.rejected';

    public const string USER_SUSPENDED = 'user.suspended';

    public const string USER_REINSTATED = 'user.reinstated';

    public const string USER_DELETED = 'user.deleted';

    public const string PRIVILEGES_UPDATED = 'privileges.updated';

    public const string SETTING_UPDATED = 'setting.updated';

    public const string PUBLICATION_APPROVED = 'publication.approved';

    public const string PUBLICATION_REJECTED = 'publication.rejected';

    public const string CATEGORY_CREATED = 'category.created';

    public const string CATEGORY_UPDATED = 'category.updated';

    public const string CATEGORY_DELETED = 'category.deleted';

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= Auth::user() instanceof User ? Auth::user() : null;

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
        ]);
    }

    /**
     * The French label an audit entry is listed under.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::FARMER_APPROVED => 'Compte agriculteur approuvé',
            self::FARMER_REJECTED => 'Compte agriculteur refusé',
            self::USER_SUSPENDED => 'Utilisateur suspendu',
            self::USER_REINSTATED => 'Utilisateur réintégré',
            self::USER_DELETED => 'Utilisateur supprimé',
            self::PRIVILEGES_UPDATED => 'Privilèges modifiés',
            self::SETTING_UPDATED => 'Paramètre modifié',
            self::PUBLICATION_APPROVED => 'Publication approuvée',
            self::PUBLICATION_REJECTED => 'Publication refusée',
            self::CATEGORY_CREATED => 'Catégorie créée',
            self::CATEGORY_UPDATED => 'Catégorie modifiée',
            self::CATEGORY_DELETED => 'Catégorie supprimée',
        ];
    }

    public static function label(string $action): string
    {
        return self::labels()[$action] ?? $action;
    }
}
