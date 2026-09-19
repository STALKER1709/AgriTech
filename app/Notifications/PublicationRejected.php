<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a farmer their publication was refused, and what to correct.
 */
final class PublicationRejected extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Model $publication,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre publication n\'a pas été retenue')
            ->greeting('Bonjour,')
            ->line(sprintf('« %s » n\'a pas été publiée.', $this->title()))
            ->line('Motif : '.$this->reason)
            ->line('Corrigez votre fiche puis soumettez-la de nouveau.')
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'publication.rejected',
            'publication_type' => $this->publication->getMorphClass(),
            'publication_id' => $this->publication->getKey(),
            'title' => $this->title(),
            'reason' => $this->reason,
        ];
    }

    private function title(): string
    {
        return $this->publication instanceof Product
            ? $this->publication->name
            : (string) $this->publication->getAttribute('title');
    }
}
