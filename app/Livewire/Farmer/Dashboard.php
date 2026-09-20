<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Training;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The farmer's sales at a glance.
 *
 * Everything here is derived from what the farmer actually owns: their
 * sub-orders, their catalogue, their threads. The platform's commission is
 * shown for what it is — what was taken from the work, not earned by it.
 */
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function mount(): void
    {
        // Route middleware already keeps the wrong roles out; the check lives
        // here too so that the component guards itself wherever it is tested
        // or rendered from.
        $farmer = Auth::user();

        abort_unless($farmer instanceof User && $farmer->isFarmer() && $farmer->isActive(), 403);
    }

    #[Computed]
    public function earnedTotal(): Money
    {
        return Money::fromInteger(
            (int) SubOrder::query()
                ->where('farmer_id', $this->farmer()->id)
                ->whereIn('status', [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered])
                ->sum('subtotal_amount'),
        );
    }

    #[Computed]
    public function commissionTotal(): Money
    {
        return Money::fromInteger(
            (int) SubOrder::query()
                ->where('farmer_id', $this->farmer()->id)
                ->whereIn('status', [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered])
                ->sum('commission_amount'),
        );
    }

    #[Computed]
    public function toPrepare(): int
    {
        return SubOrder::query()
            ->where('farmer_id', $this->farmer()->id)
            ->where('status', SubOrderStatus::Paid)
            ->count();
    }

    #[Computed]
    public function publishedProducts(): int
    {
        return Product::query()
            ->where('farmer_id', $this->farmer()->id)
            ->where('status', PublicationStatus::Published)
            ->count();
    }

    #[Computed]
    public function publishedTrainings(): int
    {
        return Training::query()
            ->where('farmer_id', $this->farmer()->id)
            ->where('status', PublicationStatus::Published)
            ->count();
    }

    #[Computed]
    public function unreadMessages(): int
    {
        return app(MessagingService::class)->unreadTotalFor($this->farmer());
    }

    /**
     * The sub-orders waiting on the farmer, newest first, for the "what
     * should I be doing" list at the top of the screen.
     *
     * @return Collection<int, SubOrder>
     */
    #[Computed]
    public function pendingWork(): Collection
    {
        return SubOrder::query()
            ->with(['order.client', 'items.product'])
            ->where('farmer_id', $this->farmer()->id)
            ->where('status', SubOrderStatus::Paid)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User && $farmer->isFarmer() && $farmer->isActive(), 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.dashboard');
    }
}
