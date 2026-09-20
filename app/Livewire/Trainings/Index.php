<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Enums\TrainingFormat;
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
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    /**
     * @return array<int, TrainingFormat>
     */
    public function formats(): array
    {
        return TrainingFormat::cases();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->format = '';
    }

    public function render(): mixed
    {
        return view('livewire.trainings.index', ['trainings' => $this->trainings()]);
    }
}
