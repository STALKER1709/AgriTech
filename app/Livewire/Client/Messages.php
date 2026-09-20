<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The client's conversation list.
 *
 * The unread badge on each thread — and the one in the navigation — both read
 * the same counter, so the screen never lies about what has been opened.
 */
#[Title('Mes messages')]
class Messages extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isClient(), 403);
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

    public function render(): mixed
    {
        return view('livewire.client.messages', ['conversations' => $this->conversations()]);
    }
}
