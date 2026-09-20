<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The client's training shelf: what was bought outright, and what the active
 * subscription opens. The titles link to the public page; the content itself
 * is only ever served by the entitlement-checked controller.
 */
#[Layout('layouts::app')]
#[Title('Mes formations')]
class MyTrainings extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isClient(), 403);
    }

    /**
     * @return Collection<int, Training>
     */
    public function purchased(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return Training::query()
            ->visibleToPublic()
            ->whereHas('purchases', fn ($query) => $query->where('client_id', $user->id))
            ->with('farmer.farmerProfile')
            ->orderByDesc('training_purchases.purchased_at')
            ->get();
    }

    /**
     * @return Collection<int, Training>
     */
    public function included(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return Training::query()
            ->visibleToPublic()
            ->includedInSubscription()
            ->with('farmer.farmerProfile')
            ->orderBy('title')
            ->get()
            ->filter(fn (Training $training): bool => $user->hasActiveSubscription());
    }

    public function render(): mixed
    {
        return view('livewire.client.my-trainings');
    }
}
