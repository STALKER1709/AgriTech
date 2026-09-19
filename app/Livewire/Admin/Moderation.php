<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\PublicationStatus;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\Training;
use App\Models\User;
use App\Services\Catalog\PublicationService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Moderating publications — business rule RG09's other half.
 *
 * Handles products and trainings together: their status machinery is the same
 * and already exists, so waiting for phase 7 would have meant either an empty
 * screen now or two screens later.
 */
#[Title('Publications à modérer')]
class Moderation extends Component
{
    public string $rejectingType = '';

    public ?int $rejectingId = null;

    public string $reason = '';

    public function mount(): void
    {
        $this->authorize(Privilege::MODERATE_PUBLICATIONS);
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::query()
            ->with(['category', 'images', 'farmer.farmerProfile'])
            ->where('status', PublicationStatus::InReview)
            ->orderBy('updated_at')
            ->get();
    }

    /**
     * @return Collection<int, Training>
     */
    public function trainings(): Collection
    {
        return Training::query()
            ->with('farmer.farmerProfile')
            ->where('status', PublicationStatus::InReview)
            ->orderBy('updated_at')
            ->get();
    }

    public function approve(string $type, int $id, PublicationService $publications): void
    {
        $publication = $this->find($type, $id);

        $this->authorize('moderate', $publication);

        $publications->approve($publication, $this->admin());

        Flux::toast(variant: 'success', text: __('Publication approuvée.'));
    }

    public function startRejection(string $type, int $id): void
    {
        $this->rejectingType = $type;
        $this->rejectingId = $id;
        $this->reason = '';
    }

    public function cancelRejection(): void
    {
        $this->rejectingType = '';
        $this->rejectingId = null;
        $this->reason = '';
    }

    public function reject(PublicationService $publications): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], attributes: ['reason' => __('motif du refus')]);

        $publication = $this->find($this->rejectingType, (int) $this->rejectingId);

        $this->authorize('moderate', $publication);

        $publications->reject($publication, $this->admin(), $this->reason);

        $this->cancelRejection();

        Flux::toast(variant: 'success', text: __('Refus enregistré. Le motif a été envoyé à l\'agriculteur.'));
    }

    private function find(string $type, int $id): Product|Training
    {
        return match ($type) {
            'product' => Product::query()->findOrFail($id),
            'training' => Training::query()->findOrFail($id),
            default => abort(404),
        };
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.moderation', [
            'products' => $this->products(),
            'trainings' => $this->trainings(),
        ]);
    }
}
