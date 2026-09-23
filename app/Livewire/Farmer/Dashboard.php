<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Training;
use App\Models\TrainingPurchase;
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

    /**
     * Le chiffre encaissé, semaine par semaine, sur les quatre dernières.
     *
     * La maquette dessine ce graphique ; les barres viennent des
     * sous-commandes réellement payées, pas d'un jeu de valeurs décoratives.
     * La semaine est celle d'Africa/Douala, comme partout ailleurs.
     *
     * @return array<int, array{label: string, amount: Money, share: float}>
     */
    #[Computed]
    public function weeklySales(): array
    {
        $timezone = (string) config('app.timezone');
        $weeks = [];

        for ($offset = 3; $offset >= 0; $offset--) {
            $start = now($timezone)->startOfWeek()->subWeeks($offset);
            $end = $start->copy()->endOfWeek();

            $weeks[] = [
                'start' => $start,
                'amount' => Money::fromInteger(
                    (int) SubOrder::query()
                        ->where('farmer_id', $this->farmer()->id)
                        ->whereIn('status', [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered])
                        ->whereBetween('created_at', [$start->copy()->utc(), $end->copy()->utc()])
                        ->sum('subtotal_amount'),
                ),
            ];
        }

        $highest = max(array_map(static fn (array $week): int => $week['amount']->amount, $weeks));

        return array_map(static fn (array $week): array => [
            'label' => $week['start']->translatedFormat('d M'),
            'amount' => $week['amount'],
            // La part de la plus haute barre, pas du total : c'est une
            // comparaison entre semaines, pas une répartition.
            // Cast explicite : en PHP, une division entière qui tombe juste
            // renvoie un entier, et la forme annoncée ici est un flottant.
            'share' => $highest > 0 ? (float) ($week['amount']->amount / $highest) : 0.0,
        ], $weeks);
    }

    /**
     * Combien de personnes ont acheté une de ses formations. C'est le
     * « 14 inscrits » de la maquette, compté pour de vrai.
     */
    #[Computed]
    public function trainingBuyers(): int
    {
        return TrainingPurchase::query()
            ->whereHas('training', fn ($query) => $query->where('farmer_id', $this->farmer()->id))
            ->count();
    }

    /**
     * Combien de publications attendent encore la modération.
     */
    #[Computed]
    public function inReview(): int
    {
        $farmer = $this->farmer();

        return Product::query()->where('farmer_id', $farmer->id)->where('status', PublicationStatus::InReview)->count()
            + Training::query()->where('farmer_id', $farmer->id)->where('status', PublicationStatus::InReview)->count();
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
