<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * One conversation, seen by the farmer.
 */
#[Title('Conversation')]
class MessageThread extends Component
{
    public Conversation $conversation;

    public string $content = '';

    public function mount(Conversation $conversation): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $conversation->includes($user), 403);

        $this->conversation = $conversation->load(['client.farmerProfile', 'farmer.farmerProfile']);

        app(MessagingService::class)->markThreadRead($conversation, $user);
    }

    /**
     * @return Collection<int, Message>
     */
    public function messages(): Collection
    {
        return $this->conversation->messages()->with('sender')->orderBy('id')->get();
    }

    public function send(MessagingService $messaging): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ], [], [
            'content' => __('message'),
        ]);

        try {
            $messaging->send($this->conversation, $user, $this->content);
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->reset('content');
    }

    public function render(): mixed
    {
        return view('livewire.farmer.message-thread');
    }
}
