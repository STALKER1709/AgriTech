<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Admin\UserModerationService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Utilisateurs')]
class Users extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    public ?int $deleting = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('farmerProfile')
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';

                $query->where(function ($inner) use ($term): void {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($this->role !== '', fn ($query) => $query->where('role', $this->role))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function suspend(int $userId, UserModerationService $service): void
    {
        $target = User::query()->findOrFail($userId);

        $this->authorize('suspend', $target);

        $service->suspend($target, $this->admin());

        Flux::toast(variant: 'success', text: __('Compte suspendu.'));
    }

    public function reinstate(int $userId, UserModerationService $service): void
    {
        $target = User::query()->findOrFail($userId);

        $this->authorize('reinstate', $target);

        $service->reinstate($target, $this->admin());

        Flux::toast(variant: 'success', text: __('Compte réintégré.'));
    }

    public function confirmDeletion(int $userId): void
    {
        $this->deleting = $userId;
    }

    public function cancelDeletion(): void
    {
        $this->deleting = null;
    }

    public function delete(UserModerationService $service): void
    {
        $target = User::query()->findOrFail($this->deleting);

        $this->authorize('delete', $target);

        $service->delete($target, $this->admin());

        $this->deleting = null;

        Flux::toast(variant: 'success', text: __('Compte supprimé et anonymisé. Son historique est conservé.'));
    }

    /**
     * @return array<int, UserRole>
     */
    public function roles(): array
    {
        return UserRole::cases();
    }

    /**
     * @return array<int, UserStatus>
     */
    public function statuses(): array
    {
        return UserStatus::cases();
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.users', ['users' => $this->users()]);
    }
}
