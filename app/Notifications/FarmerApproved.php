<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a farmer their account has been approved and their space is open.
 */
final class FarmerApproved extends Notification
{
    use Queueable;

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
            ->subject('Votre compte agriculteur est validé')
            ->greeting('Bonjour,')
            ->line('Votre compte agriculteur AgriTech vient d\'être approuvé.')
            ->line('Vous pouvez dès maintenant publier vos produits et vos formations.')
            ->action('Accéder à mon espace', route('farmer.dashboard'))
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'farmer.approved'];
    }
}
