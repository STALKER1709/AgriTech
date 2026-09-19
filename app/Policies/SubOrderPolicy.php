<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SubOrderStatus;
use App\Models\SubOrder;
use App\Models\User;

/**
 * Who may see and advance the share of an order that belongs to a farmer.
 *
 * Preparing and delivering are the farmer's to declare; the client's part is
 * over once the payment is confirmed.
 */
final class SubOrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isFarmer();
    }

    public function view(User $actor, SubOrder $subOrder): bool
    {
        return $subOrder->farmer_id === $actor->id
            || $subOrder->order->client_id === $actor->id;
    }

    public function prepare(User $actor, SubOrder $subOrder): bool
    {
        return $this->belongsToActiveFarmer($actor, $subOrder)
            && $subOrder->status === SubOrderStatus::Paid;
    }

    public function deliver(User $actor, SubOrder $subOrder): bool
    {
        return $this->belongsToActiveFarmer($actor, $subOrder)
            && in_array($subOrder->status, [
                SubOrderStatus::Paid,
                SubOrderStatus::Preparing,
            ], strict: true);
    }

    private function belongsToActiveFarmer(User $actor, SubOrder $subOrder): bool
    {
        return $actor->isFarmer()
            && $actor->isActive()
            && $subOrder->farmer_id === $actor->id;
    }
}
