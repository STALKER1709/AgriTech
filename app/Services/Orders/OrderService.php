<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\SubOrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Support\Money;
use App\Support\Quantity;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns a cart into an order.
 *
 * Business rule RG03 lives here: an order is only created for quantities the
 * products can actually supply, checked against a locked re-read rather than
 * against what the cart page happened to show a minute ago.
 *
 * What this class deliberately does not do is move stock. Business rule RG04
 * reserves that for the moment a payment is confirmed — see OrderOutcome.
 */
final class OrderService
{
    /**
     * How many times to retry when two clients check out in the same second
     * and land on the same reference. The sequence is derived from the rows
     * already stored, so a collision is possible and cheap to resolve; a
     * silent failure at the checkout button would not be.
     */
    private const int REFERENCE_ATTEMPTS = 3;

    /**
     * @throws DomainException when the cart cannot become an order
     */
    public function place(User $client, Cart $cart): Order
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn (): Order => $this->build($client, $cart));
            } catch (QueryException $exception) {
                if ($attempt >= self::REFERENCE_ATTEMPTS || ! $this->isDuplicateReference($exception)) {
                    throw $exception;
                }
            }
        }
    }

    private function build(User $client, Cart $cart): Order
    {
        $items = $cart->items()->with('product')->get();

        if ($items->isEmpty()) {
            throw new DomainException('Votre panier est vide.');
        }

        $products = $this->lockProducts($items);
        $commissionRate = $this->commissionRate();

        $order = Order::create([
            'reference' => Order::nextReference(),
            'client_id' => $client->id,
            'total_amount' => Money::zero(),
            'status' => OrderStatus::PendingPayment,
            'expires_at' => now()->addMinutes($this->cancellationDelayInMinutes()),
        ]);

        $subtotals = [];
        $index = 0;

        foreach ($this->groupByFarmer($items, $products) as $farmerId => $farmerItems) {
            $subOrder = $this->createSubOrder($order, (int) $farmerId, $index++, $commissionRate);

            $lines = [];

            foreach ($farmerItems as $item) {
                $product = $products[$item->product_id];

                $this->requireAvailability($product, $item->quantity);

                $lines[] = $subOrder->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'unit_price_snapshot' => $product->unit_price,
                    'line_total' => $product->unit_price->multipliedByQuantity($item->quantity),
                ]);
            }

            $subtotal = Money::sum(array_map(
                fn ($line): Money => $line->line_total,
                $lines,
            ));

            $subOrder->forceFill([
                'subtotal_amount' => $subtotal,
                'commission_amount' => $subtotal->percentage($commissionRate),
            ])->save();

            $subtotals[] = $subtotal;
        }

        $order->forceFill(['total_amount' => Money::sum($subtotals)])->save();

        $cart->items()->delete();

        return $order->load('subOrders.items');
    }

    /**
     * Read every product the cart touches under a row lock, in a stable order.
     *
     * Ordering by id matters: two carts holding the same two products in
     * opposite orders would otherwise be able to deadlock each other.
     *
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, Product>
     */
    private function lockProducts(Collection $items): Collection
    {
        /** @var array<int, int> $ids */
        $ids = $items->pluck('product_id')->unique()->sort()->values()->all();

        return Product::query()
            ->with('farmer')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Collection<int, CartItem>>
     */
    private function groupByFarmer(Collection $items, Collection $products): Collection
    {
        return $items
            ->groupBy(function (CartItem $item) use ($products): int {
                $product = $products[$item->product_id] ?? null;

                if ($product === null) {
                    throw new DomainException('Un produit de votre panier n\'existe plus.');
                }

                return $product->farmer_id;
            })
            ->sortKeys();
    }

    private function createSubOrder(Order $order, int $farmerId, int $index, int $commissionRate): SubOrder
    {
        return SubOrder::create([
            'order_id' => $order->id,
            'farmer_id' => $farmerId,
            'reference' => SubOrder::referenceFor($order, $index),
            'subtotal_amount' => Money::zero(),
            // Frozen now: an administrator raising the commission tomorrow
            // must not rewrite what this farmer was promised today.
            'commission_rate_snapshot' => $commissionRate,
            'commission_amount' => Money::zero(),
            'status' => SubOrderStatus::PendingPayment,
        ]);
    }

    /**
     * Business rule RG03, checked against the locked row.
     */
    private function requireAvailability(Product $product, Quantity $quantity): void
    {
        if (! $product->isVisibleToPublic()) {
            throw new DomainException(__('« :product » n\'est plus proposé à la vente.', [
                'product' => $product->name,
            ]));
        }

        if (! $product->hasStockFor($quantity)) {
            throw new DomainException(__('Stock insuffisant pour « :product » : il reste :quantity :unit.', [
                'product' => $product->name,
                'quantity' => $product->stock_quantity->format(),
                'unit' => $product->unit->shortLabel(),
            ]));
        }
    }

    /**
     * The platform's share, read from the settings.
     *
     * A missing row throws rather than defaulting to zero: a silent 0 %
     * would hand every order over commission-free, permanently and
     * invisibly. An installation without this parameter is broken, and had
     * better say so.
     */
    public function commissionRate(): int
    {
        $rate = Setting::query()
            ->where('key', Setting::PLATFORM_COMMISSION_RATE)
            ->first();

        if ($rate === null) {
            throw new DomainException('La commission de la plateforme n\'est pas configurée.');
        }

        return $rate->integerValue();
    }

    public function cancellationDelayInMinutes(): int
    {
        $minutes = Setting::query()
            ->where('key', Setting::ORDER_CANCEL_AFTER_MINUTES)
            ->first()?->integerValue() ?? 0;

        return $minutes > 0 ? $minutes : 30;
    }

    private function isDuplicateReference(QueryException $exception): bool
    {
        return $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'reference');
    }
}
