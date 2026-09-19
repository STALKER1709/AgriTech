<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a farmer a paid order is waiting to be prepared.
 */
final class SubOrderReceived extends Notification
{
    use Queueable;

    public function __construct(public readonly SubOrder $subOrder) {}

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
            ->subject(sprintf('Nouvelle commande %s', $this->subOrder->reference))
            ->greeting('Bonjour,')
            ->line(sprintf(
                'La commande %s est payée. Montant qui vous revient : %s.',
                $this->subOrder->reference,
                $this->subOrder->farmerPayout()->format(),
            ))
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sub_order.received',
            'sub_order_id' => $this->subOrder->id,
            'reference' => $this->subOrder->reference,
            'payout' => $this->subOrder->farmerPayout()->amount,
        ];
    }
}
