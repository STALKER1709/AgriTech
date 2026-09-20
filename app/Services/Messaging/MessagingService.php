<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\Training;
use App\Models\User;
use App\Notifications\NewMessage;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Client ↔ farmer conversations.
 *
 * One thread per pair, created on demand when the first message is sent.
 * Unread counts are the model's business; marking read happens when the
 * participant opens the thread. A new message notifies the other side —
 * by database row for the badge, by e-mail because the log mailer writes it
 * out for the developer to see.
 */
final class MessagingService
{
    /**
     * The one conversation between this client and this farmer, creating it
     * when it does not exist yet.
     */
    public function conversationWith(User $client, User $farmer): Conversation
    {
        return Conversation::query()
            ->where('client_id', $client->id)
            ->where('farmer_id', $farmer->id)
            ->first() ?? Conversation::create([
                'client_id' => $client->id,
                'farmer_id' => $farmer->id,
            ]);
    }

    /**
     * The conversation with the farmer who sells a product, resolved from the
     * product so the client never has to know a farmer id.
     */
    public function conversationAboutProduct(Product $product, User $client): Conversation
    {
        return $this->conversationWith($client, $product->farmer);
    }

    /**
     * The conversation with the farmer who teaches a training.
     */
    public function conversationAboutTraining(Training $training, User $client): Conversation
    {
        return $this->conversationWith($client, $training->farmer);
    }

    /**
     * Send a message inside a conversation the sender actually belongs to.
     */
    public function send(Conversation $conversation, User $sender, string $content): Message
    {
        if (! $conversation->includes($sender)) {
            throw new DomainException('Cette conversation ne vous appartient pas.');
        }

        $content = trim($content);

        if ($content === '' || mb_strlen($content) > 5000) {
            throw new DomainException('Le message doit contenir entre 1 et 5000 caractères.');
        }

        return DB::transaction(function () use ($conversation, $sender, $content): Message {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => $content,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            $recipient = $this->otherParticipant($conversation, $sender);

            if ($recipient->isActive()) {
                $recipient->notify(new NewMessage($message));
            }

            return $message;
        });
    }

    /**
     * Mark every message the other side sent as read. Called when the
     * participant opens the thread, and only for their own view.
     */
    public function markThreadRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $reader->id)
            ->update(['read_at' => now()]);
    }

    /**
     * Total unread messages across every conversation the user takes part in.
     */
    public function unreadTotalFor(User $user): int
    {
        return Message::query()
            ->whereHas('conversation', fn ($query) => $query->where(function ($inner) use ($user): void {
                $inner->where('client_id', $user->id)->orWhere('farmer_id', $user->id);
            }))
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    /**
     * The user's conversations, busiest first, with the unread badge data.
     *
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function conversationsFor(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $column = $user->isClient() ? 'client_id' : 'farmer_id';

        return Conversation::query()
            ->with(['messages' => fn ($query) => $query->latest('id')->limit(1)])
            ->with(['client.farmerProfile', 'farmer.farmerProfile'])
            ->where($column, $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private function otherParticipant(Conversation $conversation, User $sender): User
    {
        $recipientId = $conversation->client_id === $sender->id
            ? $conversation->farmer_id
            : $conversation->client_id;

        return User::query()->findOrFail($recipientId);
    }
}
