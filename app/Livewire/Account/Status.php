<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Enums\UserStatus;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tells a user whose account is not active yet where their file stands.
 *
 * Reached by anyone who is signed in but blocked from their working area: a
 * farmer who has not paid the fee, one waiting on an administrator, or a
 * suspended account.
 */
#[Title('Statut de mon compte')]
class Status extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        // An active account has no waiting to report; send it to its area.
        if ($user instanceof User && $user->isActive()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    #[Computed]
    public function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    #[Computed]
    public function registrationFee(): Money
    {
        $amount = Setting::query()
            ->where('key', Setting::FARMER_REGISTRATION_FEE)
            ->first()?->integerValue() ?? 0;

        return Money::fromInteger($amount);
    }

    #[Computed]
    public function awaitsPayment(): bool
    {
        return $this->user()->status === UserStatus::PendingPayment;
    }

    #[Computed]
    public function awaitsValidation(): bool
    {
        return $this->user()->status === UserStatus::PendingValidation;
    }

    #[Computed]
    public function wasRejected(): bool
    {
        return $this->user()->status === UserStatus::Rejected;
    }

    #[Computed]
    public function rejectionReason(): ?string
    {
        return $this->user()->farmerProfile?->rejection_reason;
    }

    public function render(): mixed
    {
        return view('livewire.account.status');
    }
}
