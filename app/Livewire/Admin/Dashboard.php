<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Training;
use App\Models\User;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Administration')]
class Dashboard extends Component
{
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

    public function render(): mixed
    {
        return view('livewire.admin.dashboard');
    }
}
