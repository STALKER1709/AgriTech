<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function render(): mixed
    {
        return view('livewire.farmer.messages', ['conversations' => $this->conversations()]);
    }
}
