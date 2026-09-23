<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mes commandes')]
class OrderList extends Component
{
    use WithPagination;

    /**
     * The two halves of the segmented control: what is still moving, and what
     * has stopped.
     */
    private const array OPEN = [
        OrderStatus::PendingPayment,
        OrderStatus::Paid,
        OrderStatus::Preparing,
    ];

    private const array CLOSED = [
        OrderStatus::Delivered,
        OrderStatus::Cancelled,
    ];

    #[Url]
    public string $tab = 'open';

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);
    }

    public function updatedTab(): void
    {
        // A status from the other half would empty the list for no visible
        // reason, so switching tab clears it.
        $this->status = '';
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function orders(): LengthAwarePaginator
    {
        return $this->query()
            ->with(['subOrders.farmer.farmerProfile', 'subOrders.items.product.images', 'payments'])
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function openCount(): int
    {
        return $this->baseQuery()->whereIn('status', self::OPEN)->count();
    }

    public function closedCount(): int
    {
        return $this->baseQuery()->whereIn('status', self::CLOSED)->count();
    }

    /**
     * The status chips of the current tab, and nothing else.
     *
     * @return array<int, OrderStatus>
     */
    public function statuses(): array
    {
        return $this->tab === 'closed' ? self::CLOSED : self::OPEN;
    }

    /**
     * The method the order was actually paid with, or null while nothing has
     * been confirmed. A pending attempt is not a payment method: it may still
     * fail, and the card would then name an operator that never paid.
     */
    public function paidWith(Order $order): ?PaymentMethod
    {
        $payment = $order->payments->first(fn (Payment $payment): bool => $payment->isSuccessful());

        return $payment?->method;
    }

    /**
     * The one-line contents of an order: "2 régimes de Plantain, 5 kg de
     * Poivre blanc", cancelled sub-orders included — they were ordered.
     */
    public function summarise(Order $order): string
    {
        return $this->itemsOf($order)
            ->map(fn (OrderItem $item): string => sprintf(
                '%s de %s',
                self::measure($item),
                $item->product->name,
            ))
            ->join(', ');
    }

    /**
     * The first lines that carry a photograph, for the thumbnail collage.
     *
     * @return Collection<int, OrderItem>
     */
    public function thumbnails(Order $order): Collection
    {
        return $this->itemsOf($order)
            ->filter(fn (OrderItem $item): bool => $item->product->images->isNotEmpty())
            ->take(2)
            ->values();
    }

    /**
     * A line's quantity with its unit: "2 régimes", "12,5 kg".
     */
    private static function measure(OrderItem $item): string
    {
        return $item->quantity->format().' '.$item->product->unit->countLabel($item->quantity);
    }

    public function farmerCount(Order $order): int
    {
        return $order->subOrders->count();
    }

    /**
     * @return Collection<int, OrderItem>
     */
    private function itemsOf(Order $order): Collection
    {
        return $order->subOrders
            ->flatMap(fn ($subOrder) => $subOrder->items)
            ->values();
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'search']);
        $this->resetPage();
    }

    /**
     * @return Builder<Order>
     */
    private function query(): Builder
    {
        return $this->baseQuery()
            ->whereIn('status', $this->tab === 'closed' ? self::CLOSED : self::OPEN)
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('reference', 'like', $term)
                    ->orWhereHas('subOrders.items.product', fn (Builder $product) => $product->where('name', 'like', $term)));
            });
    }

    /**
     * @return Builder<Order>
     */
    private function baseQuery(): Builder
    {
        return Order::query()->where('client_id', $this->client()->id);
    }

    private function client(): User
    {
        $client = Auth::user();

        abort_unless($client instanceof User, 403);

        return $client;
    }

    public function render(): mixed
    {
        return view('livewire.client.order-list', ['orders' => $this->orders()]);
    }
}
