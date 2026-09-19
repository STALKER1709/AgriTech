<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the administrators that a farmer has paid and is waiting on a decision.
 */
final class FarmerAwaitingValidation extends Notification
{
    use Queueable;

    public function __construct(public readonly User $farmer)
    {
        // Loaded here rather than read lazily in the message: the notification
        // may be built where preventLazyLoading is on, and a queued copy has
        // to carry what it needs anyway.
        $this->farmer->loadMissing('farmerProfile');
    }

    private function farmName(): string
    {
        $profile = $this->farmer->farmerProfile;

        return $profile instanceof FarmerProfile ? $profile->farm_name : '—';
    }

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
            ->subject('Nouveau compte agriculteur à examiner')
            ->greeting('Bonjour,')
            ->line(sprintf(
                '%s a réglé ses frais d\'inscription. Son compte attend votre décision.',
                $this->farmer->name,
            ))
            ->line(sprintf('Exploitation : %s', $this->farmName()))
            ->action('Examiner le dossier', route('admin.dashboard'))
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'farmer.awaiting_validation',
            'farmer_id' => $this->farmer->id,
            'farmer_name' => $this->farmer->name,
            'farm_name' => $this->farmName(),
        ];
    }
}
