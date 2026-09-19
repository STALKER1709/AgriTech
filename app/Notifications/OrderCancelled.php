<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a client their order was cancelled, and why.
 *
 * The reason is carried rather than left to be guessed: "annulée" alone is
 * what makes a client call to ask whether they were charged.
 */
final class OrderCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $reason,
        public readonly bool $refunded = false,
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
        $message = (new MailMessage)
            ->subject(sprintf('Commande %s annulée', $this->order->reference))
            ->greeting('Bonjour,')
            ->line(sprintf('La commande %s a été annulée : %s', $this->order->reference, $this->reason));

        if ($this->refunded) {
            $message->line(sprintf(
                'Le montant de %s vous est remboursé.',
                $this->order->total_amount->format(),
            ));
        }

        return $message->salutation('L\'équipe AgriTech');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order.cancelled',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'reason' => $this->reason,
            'refunded' => $this->refunded,
        ];
    }
}
