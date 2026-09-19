<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mes commandes')]
class OrderList extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with(['subOrders.farmer.farmerProfile'])
            ->where('client_id', $this->client()->id)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * @return array<int, OrderStatus>
     */
    public function statuses(): array
    {
        return OrderStatus::cases();
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
