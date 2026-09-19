<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Privilege;
use App\Models\Training;
use App\Models\User;

/**
 * Trainings get their publication screens in phase 7; their moderation is
 * handled from phase 5, since the status machinery is the same.
 */
final class TrainingPolicy
{
    public function moderate(User $actor, Training $training): bool
    {
        return $actor->hasPrivilege(Privilege::MODERATE_PUBLICATIONS)
            && $training->status === PublicationStatus::InReview;
    }
}
