<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PrivilegeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A fine-grained administrative permission, attached to admin accounts.
 *
 * Business rule RG07 is checked against these codes rather than against the
 * coarse user role.
 *
 * @property int $id
 * @property string $code
 * @property string $label
 */
#[Fillable(['code', 'label'])]
class Privilege extends Model
{
    /** @use HasFactory<PrivilegeFactory> */
    use HasFactory;

    public const string APPROVE_FARMERS = 'farmers.approve';

    public const string SUSPEND_USERS = 'users.suspend';

    public const string DELETE_USERS = 'users.delete';

    public const string MANAGE_PRIVILEGES = 'privileges.manage';

    public const string MODERATE_PUBLICATIONS = 'publications.moderate';

    public const string MANAGE_CATEGORIES = 'categories.manage';

    public const string MANAGE_SETTINGS = 'settings.manage';

    public const string VIEW_AUDIT_LOG = 'audit.view';

    public const string REFUND_PAYMENTS = 'payments.refund';

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Every privilege the application knows about, with its French label.
     *
     * @return array<string, string>
     */
    public static function catalogue(): array
    {
        return [
            self::APPROVE_FARMERS => 'Approuver les comptes agriculteurs',
            self::SUSPEND_USERS => 'Suspendre un utilisateur',
            self::DELETE_USERS => 'Supprimer un utilisateur',
            self::MANAGE_PRIVILEGES => 'Gérer les privilèges',
            self::MODERATE_PUBLICATIONS => 'Modérer les publications',
            self::MANAGE_CATEGORIES => 'Gérer les catégories',
            self::MANAGE_SETTINGS => 'Modifier les paramètres',
            self::VIEW_AUDIT_LOG => "Consulter le journal d'audit",
            self::REFUND_PAYMENTS => 'Rembourser un paiement',
        ];
    }
}
