<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Privilege;
use App\Models\Training;
use App\Models\User;

/**
 * Who may do what with a training.
 *
 * Business rule RG01 lives in canPublish(): the middleware on /agriculteur
 * already keeps an inactive farmer out of the screens, but a policy also
 * covers callers that never pass through a route.
 */
final class TrainingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isFarmer();
    }

    public function create(User $actor): bool
    {
        return $actor->canPublish();
    }

    /**
     * A farmer only ever touches their own trainings.
     */
    public function update(User $actor, Training $training): bool
    {
        return $actor->canPublish()
            && $training->farmer_id === $actor->id
            && $training->status !== PublicationStatus::Archived;
    }

    /**
     * Submitting is what business rule RG09 gates; a training already in
     * review or published has nothing to submit.
     */
    public function submit(User $actor, Training $training): bool
    {
        return $this->update($actor, $training)
            && in_array($training->status, [
                PublicationStatus::Draft,
                PublicationStatus::Rejected,
            ], strict: true);
    }

    public function archive(User $actor, Training $training): bool
    {
        return $actor->canPublish()
            && $training->farmer_id === $actor->id
            && $training->status !== PublicationStatus::Archived;
    }

    /**
     * Reading the content of a training is entitlement, not authorship:
     * business rule RG05 decides, through the model.
     */
    public function access(User $actor, Training $training): bool
    {
        return $training->isAccessibleBy($actor);
    }

    public function moderate(User $actor, Training $training): bool
    {
        return $actor->hasPrivilege(Privilege::MODERATE_PUBLICATIONS)
            && $training->status === PublicationStatus::InReview;
    }
}
