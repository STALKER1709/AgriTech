<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Enums\TrainingFormat;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Public listing of the trainings farmers sell.
 *
 * Same visibility rules as the product catalogue: only published trainings,
 * sold by an active farmer — a suspended account stops teaching as well as
 * selling.
 */
#[Layout('layouts::public')]
#[Title('Formations')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $format = '';

    /**
     * The mockup's "Incluses abonnement" pill, which is a filter like the
     * others rather than a decoration.
     */
    #[Url]
    public bool $includedOnly = false;

    public function updatedIncludedOnly(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFormat(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Training>
     */
    public function trainings(): LengthAwarePaginator
    {
        return Training::query()
            ->visibleToPublic()
            ->with('farmer.farmerProfile')
            ->when($this->search !== '', function ($query): void {
                $term = str_replace(['%', '_'], ['\%', '\_'], $this->search);

                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            })
            ->when($this->format !== '', fn ($query) => $query->where('format', $this->format))
            ->when($this->includedOnly, fn ($query) => $query->where('included_in_subscription', true))
            ->withCount('contents')
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    /**
     * How many trainings each format pill would show, and how many there are
     * in all. The mockup prints these counts; printing a made-up one would be
     * worse than printing none.
     *
     * @return array<string, int>
     */
    public function formatCounts(): array
    {
        $counts = Training::query()
            ->visibleToPublic()
            ->selectRaw('format, count(*) as aggregate')
            ->groupBy('format')
            ->pluck('aggregate', 'format');

        $byFormat = [];

        foreach (TrainingFormat::cases() as $case) {
            $byFormat[$case->value] = (int) $counts->get($case->value, 0);
        }

        $byFormat['all'] = array_sum($byFormat);

        return $byFormat;
    }

    /**
     * The cheapest plan on offer, which is what the banner quotes. Reading it
     * from the database means the price on the banner and the price charged
     * cannot drift apart.
     */
    public function entryPlan(): ?SubscriptionPlan
    {
        return SubscriptionPlan::query()->active()->orderBy('price')->first();
    }

    /**
     * @return array<int, TrainingFormat>
     */
    public function formats(): array
    {
        return TrainingFormat::cases();
    }

    /**
     * How many trainings the Pass unlocks: the banner quotes a real number,
     * not a hardcoded marketing claim.
     */
    public function includedCount(): int
    {
        return Training::query()
            ->visibleToPublic()
            ->where('included_in_subscription', true)
            ->count();
    }

    public function activeFilterCount(): int
    {
        return (int) ($this->search !== '') + (int) ($this->format !== '') + (int) $this->includedOnly;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->format = '';
        $this->includedOnly = false;
        $this->resetPage();
    }

    public function render(): mixed
    {
        return view('livewire.trainings.index', ['trainings' => $this->trainings()]);
    }
}
