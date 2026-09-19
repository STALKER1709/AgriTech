<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Privilege;
use App\Models\User;
use App\Services\Admin\PrivilegeService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Privilèges')]
class Privileges extends Component
{
    public ?int $editing = null;

    /**
     * @var array<int, string>
     */
    public array $selected = [];

    public function mount(): void
    {
        // Gated on the privilege itself, not merely on being an administrator:
        // who holds which powers is exactly the kind of thing business rule
        // RG07 is there to keep closed. The navigation hides this screen for
        // the same reason, and the two now agree.
        $this->authorize(Privilege::MANAGE_PRIVILEGES);
    }

    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::query()
            ->with('privileges')
            ->where('role', UserRole::Admin)
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public function catalogue(): array
    {
        return Privilege::catalogue();
    }

    public function edit(int $adminId): void
    {
        $target = User::query()->with('privileges')->findOrFail($adminId);

        $this->authorize('managePrivileges', $target);

        $this->editing = $adminId;
        $this->selected = $target->privileges->pluck('code')->all();
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->selected = [];
    }

    public function save(PrivilegeService $service): void
    {
        $target = User::query()->findOrFail($this->editing);

        $this->authorize('managePrivileges', $target);

        $this->validate([
            'selected' => ['array'],
            'selected.*' => ['string', 'in:'.implode(',', array_keys(Privilege::catalogue()))],
        ]);

        $service->sync($target, $this->selected, $this->admin());

        $this->cancel();

        Flux::toast(variant: 'success', text: __('Privilèges mis à jour.'));
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.privileges', ['admins' => $this->admins()]);
    }
}
