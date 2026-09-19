<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

/**
 * Who may see and pay an order.
 *
 * An order belongs to the client who placed it. A farmer involved in it sees
 * their own sub-order instead — see SubOrderPolicy — which is the share of
 * the order that concerns them and nothing more: what the client bought from
 * someone else is none of their business.
 */
final class OrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isClient();
    }

    public function view(User $actor, Order $order): bool
    {
        return $order->client_id === $actor->id;
    }

    /**
     * Paying is the client's, and only while the order is still waiting and
     * its window is open.
     */
    public function pay(User $actor, Order $order): bool
    {
        return $this->view($actor, $order)
            && $order->status === OrderStatus::PendingPayment
            && ! $order->hasExpired();
    }

    public function cancel(User $actor, Order $order): bool
    {
        return $this->view($actor, $order)
            && $order->status === OrderStatus::PendingPayment;
    }
}
