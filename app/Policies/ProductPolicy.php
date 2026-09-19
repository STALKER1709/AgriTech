<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\User;

/**
 * Who may do what with a product.
 *
 * Business rule RG01 lives in canPublish(): the middleware on /agriculteur
 * already keeps an inactive farmer out of the screens, but a policy also
 * covers callers that never pass through a route.
 */
final class ProductPolicy
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
     * A farmer only ever touches their own listings.
     */
    public function update(User $actor, Product $product): bool
    {
        return $actor->canPublish()
            && $product->farmer_id === $actor->id
            && $product->status !== PublicationStatus::Archived;
    }

    /**
     * Submitting is what business rule RG09 gates; a product already in review
     * or published has nothing to submit.
     */
    public function submit(User $actor, Product $product): bool
    {
        return $this->update($actor, $product)
            && in_array($product->status, [
                PublicationStatus::Draft,
                PublicationStatus::Rejected,
            ], strict: true);
    }

    public function archive(User $actor, Product $product): bool
    {
        return $actor->canPublish()
            && $product->farmer_id === $actor->id
            && $product->status !== PublicationStatus::Archived;
    }

    public function moderate(User $actor, Product $product): bool
    {
        return $actor->hasPrivilege(Privilege::MODERATE_PUBLICATIONS)
            && $product->status === PublicationStatus::InReview;
    }
}
