<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a farmer their product or training is now visible in the catalogue.
 */
final class PublicationApproved extends Notification
{
    use Queueable;

    public function __construct(public readonly Model $publication) {}

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
            ->subject('Votre publication est en ligne')
            ->greeting('Bonjour,')
            ->line(sprintf('« %s » est désormais visible dans le catalogue.', $this->title()))
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'publication.approved',
            'publication_type' => $this->publication->getMorphClass(),
            'publication_id' => $this->publication->getKey(),
            'title' => $this->title(),
        ];
    }

    private function title(): string
    {
        return $this->publication instanceof Product
            ? $this->publication->name
            : (string) $this->publication->getAttribute('title');
    }
}
