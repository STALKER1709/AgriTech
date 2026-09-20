<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A new message arrived. Sent on the database channel for the badge, and by
 * e-mail — which locally means a line in the log mailer's output.
 */
class NewMessage extends Notification
{
    use Queueable;

    public function __construct(private readonly Message $message) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $sender = $this->message->sender;

        return (new MailMessage)
            ->subject(__('Nouveau message sur AgriTech'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->first_name]))
            ->line(__(':name vous a envoyé un message :', ['name' => $sender->name]))
            ->line($this->excerpt())
            ->action(__('Ouvrir la conversation'), $this->threadUrl($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender->name,
            'excerpt' => $this->excerpt(),
        ];
    }

    private function excerpt(): string
    {
        return (string) str($this->message->content)->limit(120);
    }

    /**
     * Where the recipient opens the thread: the conversation is theirs on the
     * same screen whatever their role.
     */
    private function threadUrl(User $notifiable): string
    {
        $conversation = $this->message->conversation;

        return $notifiable->isClient()
            ? route('client.messages.show', ['conversation' => $conversation->id])
            : route('farmer.messages.show', ['conversation' => $conversation->id]);
    }
}
