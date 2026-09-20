<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\PublicationStatus;
use App\Models\Training;
use App\Models\User;
use App\Services\Catalog\PublicationService;
use App\Services\Trainings\TrainingService;
use DomainException;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mes formations')]
class TrainingList extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Training::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Training>
     */
    public function trainings(): LengthAwarePaginator
    {
        return Training::query()
            ->withCount('contents')
            ->where('farmer_id', $this->farmer()->id)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    /**
     * @return array<int, PublicationStatus>
     */
    public function statuses(): array
    {
        return PublicationStatus::cases();
    }

    public function submit(int $trainingId, PublicationService $publications): void
    {
        $training = Training::query()->findOrFail($trainingId);

        $this->authorize('submit', $training);

        try {
            $target = $publications->submit($training, $this->farmer());
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(
            variant: 'success',
            text: $target === PublicationStatus::Published
                ? __('Formation publiée.')
                : __('Formation soumise à modération.'),
        );
    }

    public function archive(int $trainingId, TrainingService $trainings): void
    {
        $training = Training::query()->findOrFail($trainingId);

        $this->authorize('archive', $training);

        $trainings->archive($training);

        Flux::toast(variant: 'success', text: __('Formation archivée.'));
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User, 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.training-list', ['trainings' => $this->trainings()]);
    }
}
