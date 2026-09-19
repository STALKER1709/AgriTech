<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Services\Admin\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Journal d\'audit')]
class AuditTrail extends Component
{
    use WithPagination;

    #[Url]
    public string $action = '';

    public ?int $expanded = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function entries(): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('actor')
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    /**
     * @return array<string, string>
     */
    public function actions(): array
    {
        return AuditLogger::labels();
    }

    public function toggle(int $entryId): void
    {
        $this->expanded = $this->expanded === $entryId ? null : $entryId;
    }

    public function render(): mixed
    {
        return view('livewire.admin.audit-trail', ['entries' => $this->entries()]);
    }
}
