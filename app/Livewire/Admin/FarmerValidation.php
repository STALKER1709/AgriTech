<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Admin\FarmerValidationService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Comptes agriculteurs à valider')]
class FarmerValidation extends Component
{
    use WithPagination;

    public ?int $rejecting = null;

    public string $reason = '';

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function farmers(): LengthAwarePaginator
    {
        return User::query()
            ->with('farmerProfile')
            ->where('role', UserRole::Farmer)
            ->where('status', UserStatus::PendingValidation)
            ->orderBy('created_at')
            ->paginate(10);
    }

    public function approve(int $farmerId, FarmerValidationService $service): void
    {
        $farmer = User::query()->findOrFail($farmerId);
        $admin = $this->admin();

        // Server-side, every time: the buttons are only a convenience.
        $this->authorize('decide', $farmer);

        $service->approve($farmer, $admin);

        Flux::toast(variant: 'success', text: __('Compte approuvé. L\'agriculteur a été prévenu.'));
    }

    public function startRejection(int $farmerId): void
    {
        $this->rejecting = $farmerId;
        $this->reason = '';
    }

    public function cancelRejection(): void
    {
        $this->rejecting = null;
        $this->reason = '';
    }

    public function reject(FarmerValidationService $service): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], attributes: ['reason' => __('motif du refus')]);

        $farmer = User::query()->findOrFail($this->rejecting);

        $this->authorize('decide', $farmer);

        $service->reject($farmer, $this->admin(), $this->reason);

        $this->cancelRejection();

        Flux::toast(variant: 'success', text: __('Refus enregistré. Le motif a été envoyé à l\'agriculteur.'));
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.farmer-validation', ['farmers' => $this->farmers()]);
    }
}
