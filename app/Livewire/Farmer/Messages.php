<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The farmer's conversation list. Same shape as the client's, from the other
 * side of the thread.
 */
#[Title('Messages reçus')]
class Messages extends Component
{
    /**
     * The list filter drawn on the Stitch messages screen: every thread or
     * only those carrying unread messages.
     */
    public string $filter = '';

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isFarmer(), 403);
    }

    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function conversations(): LengthAwarePaginator
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return app(MessagingService::class)->conversationsFor($user);
    }

    /**
     * The threads actually displayed after the "Non lus" tab is applied, so
     * the screen and the tab badge count the same thing.
     *
     * @return Collection<int, Conversation>
     */
    public function visibleConversations(): Collection
    {
        $conversations = collect($this->conversations()->items());

        if ($this->filter !== 'unread') {
            return $conversations;
        }

        return $conversations->filter(
            fn (Conversation $conversation): bool => $conversation->unreadCountFor($this->user()) > 0,
        )->values();
    }

    /**
     * Total unread messages across every thread on the page: the counter
     * shown inside the "Non lus" tab.
     */
    public function unreadTotal(): int
    {
        return (int) collect($this->conversations()->items())->sum(
            fn (Conversation $conversation): int => $conversation->unreadCountFor($this->user()),
        );
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.messages', ['conversations' => $this->conversations()]);
    }
}
