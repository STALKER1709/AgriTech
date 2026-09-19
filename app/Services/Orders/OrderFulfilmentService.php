<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\SubOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SubOrder;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPaid;
use App\Notifications\SubOrderReceived;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * What happens to an order once its payment has been decided.
 *
 * Business rule RG04 lives here and nowhere else: stock moves only after a
 * server-verified confirmation, under a row lock, inside the transaction that
 * confirmed the payment. Two confirmations racing for the last crate are
 * serialised by that lock, and the loser is cancelled and refunded rather
 * than allowed to drive the stock negative.
 */
final class OrderFulfilmentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * Apply a confirmed payment to its order.
     *
     * Called from inside PaymentService's transaction, so everything here
     * either happens together or not at all.
     */
    public function applyPaymentSuccess(Payment $payment, Order $order): void
    {
        // A late callback landing on an order already paid is the same
        // outcome arriving twice, not an error.
        if ($order->status === OrderStatus::Paid) {
            return;
        }

        if ($order->status !== OrderStatus::PendingPayment) {
            $this->refund($payment, $order, __('la commande avait déjà été annulée quand le paiement est arrivé.'));

            return;
        }

        $items = $this->items($order);
        $products = $this->lockProducts($items);
        $shortfall = $this->firstShortfall($items, $products);

        if ($shortfall !== null) {
            $this->cancelSubOrders($order);
            $order->cancel();
            $this->refund($payment, $order, $shortfall);

            return;
        }

        foreach ($items as $item) {
            $product = $products[$item->product_id];

            $product->forceFill([
                'stock_quantity' => $product->stock_quantity->minus($item->quantity),
            ])->save();
        }

        foreach ($order->subOrders as $subOrder) {
            $subOrder->markAsPaid();
        }

        $order->markAsPaid();
        $order->forceFill(['expires_at' => null])->save();

        $this->announcePayment($order);
    }

    /**
     * Apply a payment that failed or expired: nothing was held, so the order
     * is simply cancelled.
     */
    public function applyPaymentFailure(Payment $payment, Order $order): void
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            return;
        }

        $this->cancelSubOrders($order);
        $order->cancel();

        $this->notifyCancellation($order, __('le paiement n\'a pas abouti.'), refunded: false);
    }

    /**
     * Cancel an order left unpaid past its window.
     *
     * @return bool true when this call is what cancelled it
     */
    public function cancelExpired(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $fresh = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== OrderStatus::PendingPayment || ! $fresh->hasExpired()) {
                return false;
            }

            $fresh->load('subOrders');

            $this->cancelSubOrders($fresh);
            $fresh->cancel();

            $this->notifyCancellation(
                $fresh,
                __('elle est restée impayée au-delà du délai autorisé.'),
                refunded: false,
            );

            return true;
        });
    }

    /**
     * The farmer declares their share in preparation.
     */
    public function markPreparing(SubOrder $subOrder): void
    {
        DB::transaction(function () use ($subOrder): void {
            $subOrder->markAsPreparing();

            $this->rollUpOrderStatus($subOrder->order);
        });
    }

    /**
     * The farmer declares their share delivered.
     */
    public function markDelivered(SubOrder $subOrder): void
    {
        DB::transaction(function () use ($subOrder): void {
            $subOrder->markAsDelivered();

            $this->rollUpOrderStatus($subOrder->order);
        });
    }

    /**
     * Keep the order's status in step with its parts.
     *
     * A multi-farmer order is only delivered once every farmer still in it
     * has delivered; it is in preparation as soon as one of them starts.
     * Cancelled sub-orders are ignored rather than blocking the order for
     * ever.
     */
    private function rollUpOrderStatus(Order $order): void
    {
        $subOrders = $order->subOrders()->get()
            ->reject(fn (SubOrder $subOrder): bool => $subOrder->status === SubOrderStatus::Cancelled);

        if ($subOrders->isEmpty()) {
            return;
        }

        $target = $subOrders->every(fn (SubOrder $subOrder): bool => $subOrder->status === SubOrderStatus::Delivered)
            ? OrderStatus::Delivered
            : OrderStatus::Preparing;

        if ($order->status === $target || ! $order->status->canTransitionTo($target)) {
            return;
        }

        $order->transitionTo($target);
    }

    /**
     * @return Collection<int, OrderItem>
     */
    private function items(Order $order): Collection
    {
        return OrderItem::query()
            ->whereIn('sub_order_id', $order->subOrders->pluck('id'))
            ->get();
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     * @return Collection<int, Product>
     */
    private function lockProducts(Collection $items): Collection
    {
        /** @var array<int, int> $ids */
        $ids = $items->pluck('product_id')->unique()->sort()->values()->all();

        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * The first line the stock can no longer cover, worded for the client.
     *
     * @param  Collection<int, OrderItem>  $items
     * @param  Collection<int, Product>  $products
     */
    private function firstShortfall(Collection $items, Collection $products): ?string
    {
        foreach ($items as $item) {
            $product = $products[$item->product_id] ?? null;

            if ($product === null) {
                return __('un produit de la commande n\'existe plus.');
            }

            if (! $product->hasStockFor($item->quantity)) {
                return __('« :product » n\'était plus disponible en quantité suffisante.', [
                    'product' => $product->name,
                ]);
            }
        }

        return null;
    }

    private function cancelSubOrders(Order $order): void
    {
        foreach ($order->subOrders as $subOrder) {
            if ($subOrder->status === SubOrderStatus::PendingPayment) {
                $subOrder->cancel();
            }
        }
    }

    private function refund(Payment $payment, Order $order, string $reason): void
    {
        $refunded = $this->gateway->refund($payment);

        if ($refunded) {
            $payment->markAsRefunded();
        } else {
            Log::warning('Remboursement refusé par la passerelle.', [
                'reference' => $payment->provider_reference,
                'commande' => $order->reference,
            ]);
        }

        $this->notifyCancellation($order, $reason, refunded: $refunded);
    }

    private function announcePayment(Order $order): void
    {
        $order->client->notify(new OrderPaid($order));

        foreach ($order->subOrders as $subOrder) {
            $subOrder->farmer->notify(new SubOrderReceived($subOrder));
        }
    }

    private function notifyCancellation(Order $order, string $reason, bool $refunded): void
    {
        $order->client->notify(new OrderCancelled($order, $reason, $refunded));
    }
}
