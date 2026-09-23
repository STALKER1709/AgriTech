<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Subscription;
use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The client's training shelf: what was bought outright, and what the active
 * subscription opens.
 *
 * The distinction matters to the reader, and not only for display: a bought
 * training stays theirs, one opened by the Pass closes with it. That is the
 * split the filter offers, rather than a progress that nothing records.
 */
#[Layout('layouts::app')]
#[Title('Mes formations')]
class MyTrainings extends Component
{
    /**
     * all | purchased | included
     */
    #[Url]
    public string $filter = 'all';

    /**
     * Both lists are read several times per render — once for the counters,
     * once for the feed, once per card to tell bought from included. Livewire
     * only hydrates public properties, so this stays a private memo for the
     * duration of one request.
     *
     * @var array<string, Collection<int, Training>>
     */
    private array $memo = [];

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isClient(), 403);
    }

    /**
     * Everything the client may open right now, in one list.
     *
     * @return Collection<int, Training>
     */
    public function trainings(): Collection
    {
        return match ($this->filter) {
            'purchased' => $this->purchased(),
            'included' => $this->included(),
            default => $this->purchased()
                ->concat($this->included())
                ->unique('id')
                ->values(),
        };
    }

    /**
     * @return Collection<int, Training>
     */
    public function purchased(): Collection
    {
        return $this->memo['purchased'] ??= $this->fetchPurchased();
    }

    /**
     * @return Collection<int, Training>
     */
    private function fetchPurchased(): Collection
    {
        $user = $this->client();

        if (! $user instanceof User) {
            return collect();
        }

        return Training::query()
            ->visibleToPublic()
            ->whereHas('purchases', fn ($query) => $query->where('client_id', $user->id))
            ->with('farmer.farmerProfile')
            ->withCount('contents')
            // La date d'achat vit dans une table qui n'est pas jointe : un
            // `orderByDesc('training_purchases.purchased_at')` échoue à
            // l'exécution. La sous-requête va la chercher là où elle est.
            ->orderByDesc(
                TrainingPurchase::query()
                    ->select('purchased_at')
                    ->whereColumn('training_id', 'trainings.id')
                    ->where('client_id', $user->id)
                    ->limit(1),
            )
            ->get();
    }

    /**
     * @return Collection<int, Training>
     */
    public function included(): Collection
    {
        return $this->memo['included'] ??= $this->fetchIncluded();
    }

    /**
     * @return Collection<int, Training>
     */
    private function fetchIncluded(): Collection
    {
        $user = $this->client();

        if (! $user instanceof User || ! $user->hasActiveSubscription()) {
            return collect();
        }

        return Training::query()
            ->visibleToPublic()
            ->includedInSubscription()
            ->with('farmer.farmerProfile')
            ->withCount('contents')
            ->orderBy('title')
            ->get();
    }

    /**
     * Whether a training is on the shelf because it was bought. A training
     * both bought and included reads as bought: that is the stronger claim,
     * and the one that survives the subscription ending.
     */
    public function wasPurchased(Training $training): bool
    {
        return $this->purchased()->contains('id', $training->id);
    }

    /**
     * The module to open first, which is simply the first one.
     */
    public function firstModuleTitle(Training $training): ?string
    {
        return $training->contents()->orderBy('position')->value('title');
    }

    public function subscription(): ?Subscription
    {
        $user = $this->client();

        return $user instanceof User
            ? app(SubscriptionService::class)->runningSubscription($user)
            : null;
    }

    private function client(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public function render(): mixed
    {
        return view('livewire.client.my-trainings');
    }
}
