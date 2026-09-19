<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\SubOrderStatus;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Orders\OrderFulfilmentService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The orders a farmer has to prepare.
 *
 * A farmer sees their own share of each order and nothing else: what the same
 * client bought from another farmer is not theirs to read.
 */
#[Title('Mes commandes reçues')]
class OrderList extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SubOrder::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, SubOrder>
     */
    public function subOrders(): LengthAwarePaginator
    {
        return SubOrder::query()
            ->with(['order.client', 'items.product'])
            ->where('farmer_id', $this->farmer()->id)
            // Only shares that were actually paid for. A sub-order still
            // awaiting payment is not work, and a cancelled one never was:
            // nothing cancels a share once it has been paid, so what is
            // filtered out here is only what the farmer never had to do.
            ->whereIn('status', array_column($this->statuses(), 'value'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * @return array<int, SubOrderStatus>
     */
    public function statuses(): array
    {
        return [
            SubOrderStatus::Paid,
            SubOrderStatus::Preparing,
            SubOrderStatus::Delivered,
        ];
    }

    public function prepare(int $subOrderId, OrderFulfilmentService $fulfilment): void
    {
        $subOrder = $this->subOrder($subOrderId);

        $this->authorize('prepare', $subOrder);

        $fulfilment->markPreparing($subOrder);

        Flux::toast(variant: 'success', text: __('Commande passée en préparation.'));
    }

    public function deliver(int $subOrderId, OrderFulfilmentService $fulfilment): void
    {
        $subOrder = $this->subOrder($subOrderId);

        $this->authorize('deliver', $subOrder);

        $fulfilment->markDelivered($subOrder);

        Flux::toast(variant: 'success', text: __('Commande marquée comme livrée.'));
    }

    private function subOrder(int $subOrderId): SubOrder
    {
        return SubOrder::query()->with('order')->findOrFail($subOrderId);
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User, 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.order-list', ['subOrders' => $this->subOrders()]);
    }
}
