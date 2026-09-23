<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Training;
use App\Models\User;
use App\Notifications\FarmerApproved;
use App\Notifications\FarmerAwaitingValidation;
use App\Notifications\FarmerRejected;
use App\Notifications\NewMessage;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPaid;
use App\Notifications\PublicationApproved;
use App\Notifications\PublicationRejected;
use App\Notifications\SubOrderReceived;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Turns a stored notification row into a line of the notifications screen.
 *
 * The presentation cannot live on the notification classes: by the time the
 * screen reads a row, the object that produced it is long gone — only the
 * class name in `notifications.type` and the array it wrote in
 * `notifications.data` remain. So the mapping lives here, keyed on that class
 * name rather than on the `type` field inside the payload, which the oldest
 * rows do not all carry.
 *
 * A row whose class is unknown still renders. That is deliberate, and unlike
 * the rule that makes a `PaymentPurpose` without an effect throw: a payment
 * that credits nothing is a bug to stop, whereas a notification nobody
 * styled is a line to show plainly. Losing the user's history to a renamed
 * class would be the worse failure.
 */
final readonly class NotificationPresenter
{
    /**
     * The chips of the filter carousel, in order. The mockup names Commandes,
     * Paiements, Formations and Système; these are the four the data can
     * actually fill.
     */
    public const array CATEGORIES = [
        'orders' => 'Commandes',
        'publications' => 'Publications',
        'messages' => 'Messages',
        'account' => 'Compte',
    ];

    public function __construct(private User $user) {}

    /**
     * @return array{
     *     category: string,
     *     icon: string,
     *     tint: string,
     *     title: string,
     *     body: string,
     *     url: string|null,
     *     tag: array{icon: string, label: string}|null,
     * }
     */
    public function present(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        return match ($notification->type) {
            OrderPaid::class => $this->orderPaid($data),
            OrderCancelled::class => $this->orderCancelled($data),
            SubOrderReceived::class => $this->subOrderReceived($data),
            NewMessage::class => $this->newMessage($data),
            PublicationApproved::class => $this->publicationApproved($data),
            PublicationRejected::class => $this->publicationRejected($data),
            FarmerApproved::class => $this->farmerApproved(),
            FarmerRejected::class => $this->farmerRejected($data),
            FarmerAwaitingValidation::class => $this->farmerAwaitingValidation($data),
            default => $this->unknown(),
        };
    }

    public function category(DatabaseNotification $notification): string
    {
        return $this->present($notification)['category'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function orderPaid(array $data): array
    {
        $amount = Money::fromInteger($this->int($data, 'total_amount'));

        return [
            'category' => 'orders',
            'icon' => 'payments',
            'tint' => 'tertiary',
            'title' => (string) __('Paiement confirmé (:amount)', ['amount' => $amount->format()]),
            'body' => (string) __('La commande :reference est payée ; les producteurs la préparent.', [
                'reference' => $this->string($data, 'reference'),
            ]),
            'url' => $this->orderUrl($data),
            'tag' => ['icon' => 'check_circle', 'label' => $amount->format()],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function orderCancelled(array $data): array
    {
        $refunded = (bool) ($data['refunded'] ?? false);

        return [
            'category' => 'orders',
            'icon' => 'cancel',
            'tint' => 'error',
            'title' => (string) __('Commande :reference annulée', ['reference' => $this->string($data, 'reference')]),
            'body' => $this->string($data, 'reason') !== ''
                ? $this->string($data, 'reason')
                : (string) __('La commande a été annulée.'),
            'url' => $this->orderUrl($data),
            'tag' => $refunded ? ['icon' => 'undo', 'label' => (string) __('Remboursée')] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function subOrderReceived(array $data): array
    {
        $payout = Money::fromInteger($this->int($data, 'payout'));

        return [
            'category' => 'orders',
            'icon' => 'local_shipping',
            'tint' => 'secondary',
            'title' => (string) __('Nouvelle commande :reference', ['reference' => $this->string($data, 'reference')]),
            'body' => (string) __('Elle est payée et vous revient à préparer.'),
            'url' => route('farmer.orders'),
            'tag' => ['icon' => 'payments', 'label' => $payout->format()],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function newMessage(array $data): array
    {
        $conversation = $this->int($data, 'conversation_id');

        return [
            'category' => 'messages',
            'icon' => 'chat',
            'tint' => 'primary',
            'title' => (string) __('Message de :name', ['name' => $this->string($data, 'sender_name')]),
            'body' => $this->string($data, 'excerpt'),
            'url' => $conversation > 0
                ? route($this->user->isClient() ? 'client.messages.show' : 'farmer.messages.show', ['conversation' => $conversation])
                : null,
            'tag' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function publicationApproved(array $data): array
    {
        return [
            'category' => 'publications',
            'icon' => 'verified_user',
            'tint' => 'primary',
            'title' => (string) __('« :title » est publiée', ['title' => $this->string($data, 'title')]),
            'body' => (string) __('Votre publication est visible dans le catalogue.'),
            'url' => $this->publicationUrl($data),
            'tag' => ['icon' => 'visibility', 'label' => (string) __('En ligne')],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function publicationRejected(array $data): array
    {
        return [
            'category' => 'publications',
            'icon' => 'report',
            'tint' => 'error',
            'title' => (string) __('« :title » a été refusée', ['title' => $this->string($data, 'title')]),
            'body' => $this->string($data, 'reason'),
            'url' => $this->publicationUrl($data),
            'tag' => null,
        ];
    }

    /**
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function farmerApproved(): array
    {
        return [
            'category' => 'account',
            'icon' => 'verified',
            'tint' => 'primary',
            'title' => (string) __('Votre compte agriculteur est validé'),
            'body' => (string) __('Vous pouvez publier vos produits et vos formations.'),
            'url' => route('farmer.dashboard'),
            'tag' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function farmerRejected(array $data): array
    {
        return [
            'category' => 'account',
            'icon' => 'gpp_bad',
            'tint' => 'error',
            'title' => (string) __('Votre demande a été refusée'),
            'body' => $this->string($data, 'reason'),
            'url' => route('account.status'),
            'tag' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function farmerAwaitingValidation(array $data): array
    {
        return [
            'category' => 'account',
            'icon' => 'how_to_reg',
            'tint' => 'secondary',
            'title' => (string) __('Un compte agriculteur attend validation'),
            'body' => (string) __(':farm — :name', [
                'farm' => $this->string($data, 'farm_name'),
                'name' => $this->string($data, 'farmer_name'),
            ]),
            'url' => route('admin.farmers'),
            'tag' => null,
        ];
    }

    /**
     * @return array{category: string, icon: string, tint: string, title: string, body: string, url: string|null, tag: array{icon: string, label: string}|null}
     */
    private function unknown(): array
    {
        return [
            'category' => 'account',
            'icon' => 'notifications',
            'tint' => 'secondary',
            'title' => (string) __('Notification'),
            'body' => (string) __('Cette notification n\'a pas d\'affichage dédié.'),
            'url' => null,
            'tag' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function orderUrl(array $data): ?string
    {
        $reference = $this->string($data, 'reference');

        if ($reference === '' || ! $this->user->isClient()) {
            return null;
        }

        // The order screen is keyed on the reference, and belongs to its own
        // client: an order that vanished leaves the line without a link
        // rather than a dead one.
        return Order::query()->where('reference', $reference)->where('client_id', $this->user->id)->exists()
            ? route('client.orders.show', ['order' => $reference])
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function publicationUrl(array $data): ?string
    {
        return match ($this->string($data, 'publication_type')) {
            Product::class => route('farmer.products'),
            Training::class => route('farmer.trainings'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }
}
