<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Enums\TrainingContentType;
use App\Models\Conversation;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\User;
use App\Services\Trainings\TrainingAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Reading a training one module at a time.
 *
 * The screen shows nothing the entitlement does not already allow: it is the
 * same check the content controller runs, applied one step earlier so that a
 * client without access sees a 403 rather than a page of locked rows. The
 * files themselves are still served only by that controller — this component
 * never touches the private disk.
 */
#[Layout('layouts::app')]
class Reader extends Component
{
    public Training $training;

    public TrainingContent $content;

    public function mount(Training $training, ?TrainingContent $content = null): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless(app(TrainingAccessService::class)->canAccess($training, $user), 403);

        $this->training = $training->load(['farmer.farmerProfile', 'contents']);

        abort_if($this->training->contents->isEmpty(), 404);

        // A module of another training would read someone else's shelf.
        if ($content instanceof TrainingContent && $content->training_id !== $training->id) {
            abort(404);
        }

        $this->content = $content ?? $this->modules()->first();
    }

    /**
     * The modules in reading order.
     *
     * @return Collection<int, TrainingContent>
     */
    public function modules(): Collection
    {
        return $this->training->contents->sortBy('position')->values();
    }

    /**
     * The documents among them, which the mockup lists in a tab of their own.
     * The tab is only worth showing when the training mixes the two kinds.
     *
     * @return Collection<int, TrainingContent>
     */
    public function documents(): Collection
    {
        return $this->modules()
            ->filter(fn (TrainingContent $content): bool => $content->type === TrainingContentType::Pdf)
            ->values();
    }

    public function hasMixedContent(): bool
    {
        return $this->documents()->isNotEmpty() && $this->documents()->count() !== $this->modules()->count();
    }

    /**
     * The module's rank, which is what the reader's "Module 2 sur 5" says.
     */
    public function currentIndex(): int
    {
        return (int) $this->modules()->search(
            fn (TrainingContent $content): bool => $content->id === $this->content->id,
        ) + 1;
    }

    public function previous(): ?TrainingContent
    {
        return $this->modules()->get($this->currentIndex() - 2);
    }

    public function next(): ?TrainingContent
    {
        return $this->modules()->get($this->currentIndex());
    }

    public function open(int $contentId): void
    {
        $content = $this->modules()->firstWhere('id', $contentId);

        abort_unless($content instanceof TrainingContent, 404);

        $this->content = $content;
    }

    /**
     * Where "Poser une question" leads: the thread with this farmer if one is
     * already open, the messaging screen otherwise. The conversation itself is
     * created by the messaging service, never here.
     */
    public function askUrl(): string
    {
        $user = Auth::user();

        $conversation = $user instanceof User
            ? Conversation::query()
                ->where('client_id', $user->id)
                ->where('farmer_id', $this->training->farmer_id)
                ->first()
            : null;

        return $conversation instanceof Conversation
            ? route('client.messages.show', ['conversation' => $conversation->id])
            : route('client.messages');
    }

    public function render(): mixed
    {
        return view('livewire.trainings.reader')->title($this->training->title);
    }
}
