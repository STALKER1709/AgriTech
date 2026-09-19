<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a farmer their account was refused, and why.
 *
 * The reason is mandatory: a refusal without one leaves the farmer with
 * nothing to correct, and the same file comes back unchanged.
 */
final class FarmerRejected extends Notification
{
    use Queueable;

    public function __construct(public readonly string $reason) {}

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
            ->subject('Votre demande de compte agriculteur')
            ->greeting('Bonjour,')
            ->line('Votre demande de compte agriculteur AgriTech n\'a pas été retenue.')
            ->line('Motif : '.$this->reason)
            ->line('Vous pouvez corriger votre dossier et nous contacter pour un nouvel examen.')
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => 'farmer.rejected', 'reason' => $this->reason];
    }
}
