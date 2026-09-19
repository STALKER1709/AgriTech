<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\PublicationStatus;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Training;
use App\Models\User;
use App\Notifications\PublicationApproved;
use App\Notifications\PublicationRejected;
use App\Services\Admin\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Moves products and trainings through moderation.
 *
 * Business rule RG09: a new publication reaches Published through InReview,
 * unless prior moderation has been switched off in the settings. That setting
 * is read here and nowhere else, so the rule has exactly one home.
 *
 * Business rule RG01 is checked here too rather than only at the route: a
 * policy and a middleware both protect the screens, but this protects every
 * other caller as well.
 */
final class PublicationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Submit a draft. Whether that means "in review" or "published" is the
     * platform's decision, not the farmer's.
     *
     * The union type is deliberate: both models carry the same status
     * machinery, and saying so is more honest than an interface invented to
     * make one signature look tidier.
     */
    public function submit(Product|Training $publication, User $farmer): PublicationStatus
    {
        if (! $farmer->canPublish()) {
            throw new DomainException(
                'Un agriculteur ne peut rien publier tant que son compte n\'est pas actif.',
            );
        }

        $target = $this->priorModerationEnabled()
            ? PublicationStatus::InReview
            : PublicationStatus::Published;

        $publication->transitionTo($target);

        $publication->forceFill(['rejection_reason' => null])->save();

        return $target;
    }

    public function approve(Product|Training $publication, User $admin): void
    {
        DB::transaction(function () use ($publication, $admin): void {
            $before = ['status' => $publication->status->value];

            $publication->publish();
            $publication->forceFill(['rejection_reason' => null])->save();

            $this->audit->record(
                action: AuditLogger::PUBLICATION_APPROVED,
                auditable: $publication,
                before: $before,
                after: ['status' => $publication->refresh()->status->value],
                actor: $admin,
            );
        });

        $publication->farmer->notify(new PublicationApproved($publication));
    }

    public function reject(Product|Training $publication, User $admin, string $reason): void
    {
        DB::transaction(function () use ($publication, $admin, $reason): void {
            $before = ['status' => $publication->status->value];

            $publication->rejectPublication();
            $publication->forceFill(['rejection_reason' => $reason])->save();

            $this->audit->record(
                action: AuditLogger::PUBLICATION_REJECTED,
                auditable: $publication,
                before: $before,
                after: [
                    'status' => $publication->refresh()->status->value,
                    'rejection_reason' => $reason,
                ],
                actor: $admin,
            );
        });

        $publication->farmer->notify(new PublicationRejected($publication, $reason));
    }

    public function priorModerationEnabled(): bool
    {
        return Setting::query()
            ->where('key', Setting::PRIOR_MODERATION_ENABLED)
            ->first()?->booleanValue() ?? true;
    }
}
