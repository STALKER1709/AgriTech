<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a client their payment was confirmed and their order is on its way.
 */
final class OrderPaid extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

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
            ->subject(sprintf('Commande %s confirmée', $this->order->reference))
            ->greeting('Bonjour,')
            ->line(sprintf(
                'Votre paiement de %s a été confirmé. La commande %s est transmise aux agriculteurs.',
                $this->order->total_amount->format(),
                $this->order->reference,
            ))
            ->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.paid',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'total_amount' => $this->order->total_amount->amount,
        ];
    }
}
