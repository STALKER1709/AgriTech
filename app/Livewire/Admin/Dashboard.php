<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Training;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The platform's numbers, for the administrator.
 *
 * Everything is a single aggregate query per tile: the dashboard is the one
 * screen that reads everything, and it must not become the one screen that
 * queries everything a hundred times.
 */
#[Title('Administration')]
class Dashboard extends Component
{
    public function mount(): void
    {
        // Same reasoning as every admin screen: the role opens the area, and
        // the component guards itself too.
        $admin = Auth::user();

        abort_unless($admin instanceof User && $admin->isAdmin(), 403);
    }

    #[Computed]
    public function farmersAwaitingValidation(): int
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->where('status', UserStatus::PendingValidation)
            ->count();
    }

    #[Computed]
    public function publicationsAwaitingModeration(): int
    {
        return Product::query()->where('status', PublicationStatus::InReview)->count()
            + Training::query()->where('status', PublicationStatus::InReview)->count();
    }

    #[Computed]
    public function activeFarmers(): int
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->where('status', UserStatus::Active)
            ->count();
    }

    #[Computed]
    public function clients(): int
    {
        return User::query()
            ->where('role', UserRole::Client)
            ->where('status', UserStatus::Active)
            ->count();
    }

    #[Computed]
    public function collectedToday(): Money
    {
        $total = Payment::query()
            ->where('status', PaymentStatus::Succeeded)
            ->whereDate('confirmed_at', today())
            ->sum('amount');

        return Money::fromInteger((int) $total);
    }

    #[Computed]
    public function paymentsAwaitingOutcome(): int
    {
        return Payment::query()->awaitingOutcome()->count();
    }

    #[Computed]
    public function ordersPendingPayment(): int
    {
        return Order::query()->where('status', OrderStatus::PendingPayment)->count();
    }

    #[Computed]
    public function ordersDelivered(): int
    {
        return Order::query()->where('status', OrderStatus::Delivered)->count();
    }

    #[Computed]
    public function commissionTotal(): Money
    {
        return Money::fromInteger(
            (int) Payment::query()
                ->where('status', PaymentStatus::Succeeded)
                ->sum('amount'),
        );
    }

    #[Computed]
    public function activeSubscriptions(): int
    {
        return Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->count();
    }

    public function render(): mixed
    {
        return view('livewire.admin.dashboard');
    }
}
