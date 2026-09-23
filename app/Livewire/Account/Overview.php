<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Subscription;
use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The account hub: who you are, three figures, and the way to everything
 * else.
 *
 * It owns no data of its own. Every figure is a count the screen it links to
 * would show, and every entry leads to a page that already exists — a hub
 * that offers a destination it cannot reach is worse than no hub.
 */
#[Layout('layouts::app')]
#[Title('Mon compte')]
class Overview extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user() instanceof User, 403);
    }

    public function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * The three figures of the mockup's bento grid, one set per role.
     *
     * @return array<int, array{icon: string, value: int|string, label: string}>
     */
    public function stats(): array
    {
        $user = $this->user();

        return match (true) {
            $user->isFarmer() => $this->farmerStats($user),
            $user->isAdmin() => $this->adminStats(),
            default => $this->clientStats($user),
        };
    }

    /**
     * @return array<int, array{icon: string, value: int|string, label: string}>
     */
    private function clientStats(User $user): array
    {
        $subscription = app(SubscriptionService::class)->runningSubscription($user);

        return [
            [
                'icon' => 'local_mall',
                'value' => Order::query()->where('client_id', $user->id)->count(),
                'label' => (string) __('Commandes'),
            ],
            [
                'icon' => 'school',
                'value' => TrainingPurchase::query()->where('client_id', $user->id)->count(),
                'label' => (string) __('Formations'),
            ],
            [
                'icon' => 'workspace_premium',
                'value' => $subscription instanceof Subscription ? $subscription->plan->name : (string) __('Aucun'),
                'label' => (string) __('Pass'),
            ],
        ];
    }

    /**
     * @return array<int, array{icon: string, value: int|string, label: string}>
     */
    private function farmerStats(User $user): array
    {
        return [
            [
                'icon' => 'inventory_2',
                'value' => Product::query()->where('farmer_id', $user->id)->where('status', PublicationStatus::Published)->count(),
                'label' => (string) __('Produits publiés'),
            ],
            [
                'icon' => 'school',
                'value' => Training::query()->where('farmer_id', $user->id)->where('status', PublicationStatus::Published)->count(),
                'label' => (string) __('Formations'),
            ],
            [
                'icon' => 'local_shipping',
                'value' => SubOrder::query()
                    ->where('farmer_id', $user->id)
                    ->whereIn('status', [SubOrderStatus::Paid, SubOrderStatus::Preparing])
                    ->count(),
                'label' => (string) __('À préparer'),
            ],
        ];
    }

    /**
     * @return array<int, array{icon: string, value: int|string, label: string}>
     */
    private function adminStats(): array
    {
        return [
            [
                'icon' => 'how_to_reg',
                'value' => User::query()->where('status', UserStatus::PendingValidation)->count(),
                'label' => (string) __('À valider'),
            ],
            [
                'icon' => 'gavel',
                'value' => Product::query()->where('status', PublicationStatus::InReview)->count()
                    + Training::query()->where('status', PublicationStatus::InReview)->count(),
                'label' => (string) __('À modérer'),
            ],
            [
                'icon' => 'receipt_long',
                'value' => Order::query()->where('status', OrderStatus::PendingPayment)->count(),
                'label' => (string) __('À payer'),
            ],
        ];
    }

    /**
     * The entries of the first section, which differ by role.
     *
     * @return array<int, array{icon: string, title: string, detail: string, href: string}>
     */
    public function activities(): array
    {
        $user = $this->user();

        if ($user->isFarmer()) {
            return [
                ['icon' => 'inventory_2', 'title' => (string) __('Mes produits'), 'detail' => (string) __('Publier, corriger, retirer'), 'href' => route('farmer.products')],
                ['icon' => 'play_lesson', 'title' => (string) __('Mes formations'), 'detail' => (string) __('Vos cours et leurs modules'), 'href' => route('farmer.trainings')],
                ['icon' => 'package_2', 'title' => (string) __('Commandes reçues'), 'detail' => (string) __('Ce qui est payé et à préparer'), 'href' => route('farmer.orders')],
                ['icon' => 'forum', 'title' => (string) __('Messages'), 'detail' => (string) __('Vos échanges avec les acheteurs'), 'href' => route('farmer.messages')],
            ];
        }

        if ($user->isAdmin()) {
            // Le rôle ouvre /admin ; il n'autorise rien à l'intérieur. Une
            // entrée derrière un privilège que ce compte n'a pas mènerait à
            // un 403, donc elle n'est pas proposée — c'est déjà la règle de
            // la navigation latérale.
            return collect([
                [Privilege::APPROVE_FARMERS, 'how_to_reg', __('Agriculteurs à valider'), __('Approuver ou refuser un compte'), 'admin.farmers'],
                [Privilege::MODERATE_PUBLICATIONS, 'gavel', __('Modération'), __('Publications en attente'), 'admin.moderation'],
                [Privilege::SUSPEND_USERS, 'group', __('Utilisateurs'), __('Comptes, statuts, suppressions'), 'admin.users'],
                [Privilege::VIEW_AUDIT_LOG, 'receipt_long', __('Journal d\'audit'), __('Qui a fait quoi, et quand'), 'admin.audit'],
            ])
                ->filter(fn (array $entry): bool => Gate::allows($entry[0]))
                ->map(fn (array $entry): array => [
                    'icon' => $entry[1],
                    'title' => (string) $entry[2],
                    'detail' => (string) $entry[3],
                    'href' => route($entry[4]),
                ])
                ->values()
                ->all();
        }

        return [
            ['icon' => 'package_2', 'title' => (string) __('Mes commandes'), 'detail' => (string) __('Du paiement à la livraison'), 'href' => route('client.orders')],
            ['icon' => 'play_lesson', 'title' => (string) __('Mes formations'), 'detail' => (string) __('Ce que vous pouvez lire'), 'href' => route('client.trainings')],
            ['icon' => 'stars', 'title' => (string) __('Pass Formations'), 'detail' => $this->passDetail(), 'href' => route('client.subscriptions')],
            ['icon' => 'shopping_cart', 'title' => (string) __('Mon panier'), 'detail' => (string) __('Ce qui attend d\'être commandé'), 'href' => route('client.cart')],
        ];
    }

    /**
     * The entries every account shares.
     *
     * @return array<int, array{icon: string, title: string, detail: string, href: string}>
     */
    public function preferences(): array
    {
        return [
            ['icon' => 'badge', 'title' => (string) __('Profil'), 'detail' => (string) __('Nom, e-mail, téléphone'), 'href' => route('profile.edit')],
            ['icon' => 'lock_reset', 'title' => (string) __('Sécurité'), 'detail' => (string) __('Mot de passe'), 'href' => route('security.edit')],
            ['icon' => 'palette', 'title' => (string) __('Apparence'), 'detail' => (string) __('Thème clair ou sombre'), 'href' => route('appearance.edit')],
            ['icon' => 'notifications', 'title' => (string) __('Notifications'), 'detail' => (string) __('Tout ce que la plateforme vous a dit'), 'href' => route('notifications')],
        ];
    }

    private function passDetail(): string
    {
        $subscription = app(SubscriptionService::class)->runningSubscription($this->user());

        if (! $subscription instanceof Subscription) {
            return (string) __('Aucun Pass actif');
        }

        return (string) __('Actif jusqu\'au :date', [
            'date' => $subscription->ends_at?->timezone(config('app.timezone'))->translatedFormat('d/m/Y'),
        ]);
    }

    public function render(): mixed
    {
        return view('livewire.account.overview');
    }
}
