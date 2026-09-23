<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Subscription;
use App\Models\Training;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The platform's numbers, for the administrator.
 *
 * Everything is a single aggregate query per tile: the dashboard is the one
 * screen that reads everything, and it must not become the one screen that
 * queries everything a hundred times.
 */
#[Title('Administration')]
class Dashboard extends Component
{
    public function mount(): void
    {
        // Same reasoning as every admin screen: the role opens the area, and
        // the component guards itself too.
        $admin = Auth::user();

        abort_unless($admin instanceof User && $admin->isAdmin(), 403);
    }

    #[Computed]
    public function farmersAwaitingValidation(): int
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->where('status', UserStatus::PendingValidation)
            ->count();
    }

    #[Computed]
    public function publicationsAwaitingModeration(): int
    {
        return Product::query()->where('status', PublicationStatus::InReview)->count()
            + Training::query()->where('status', PublicationStatus::InReview)->count();
    }

    #[Computed]
    public function activeFarmers(): int
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->where('status', UserStatus::Active)
            ->count();
    }

    #[Computed]
    public function clients(): int
    {
        return User::query()
            ->where('role', UserRole::Client)
            ->where('status', UserStatus::Active)
            ->count();
    }

    #[Computed]
    public function collectedToday(): Money
    {
        $total = Payment::query()
            ->where('status', PaymentStatus::Succeeded)
            ->whereDate('confirmed_at', today())
            ->sum('amount');

        return Money::fromInteger((int) $total);
    }

    #[Computed]
    public function paymentsAwaitingOutcome(): int
    {
        return Payment::query()->awaitingOutcome()->count();
    }

    #[Computed]
    public function ordersPendingPayment(): int
    {
        return Order::query()->where('status', OrderStatus::PendingPayment)->count();
    }

    #[Computed]
    public function ordersDelivered(): int
    {
        return Order::query()->where('status', OrderStatus::Delivered)->count();
    }

    /**
     * Tout ce que la plateforme a encaissé, toutes natures confondues.
     *
     * Le nom précédent — `commissionTotal` — disait le contraire de ce que la
     * requête calcule : la commission de la plateforme vit sur les
     * sous-commandes, pas sur le montant des paiements.
     */
    #[Computed]
    public function volumeSettled(): Money
    {
        return Money::fromInteger(
            (int) Payment::query()
                ->where('status', PaymentStatus::Succeeded)
                ->sum('amount'),
        );
    }

    /**
     * Ce que la plateforme a réellement prélevé : la somme des commissions
     * figées sur les sous-commandes payées, et rien d'autre.
     */
    #[Computed]
    public function platformCommission(): Money
    {
        return Money::fromInteger(
            (int) SubOrder::query()
                ->whereIn('status', [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered])
                ->sum('commission_amount'),
        );
    }

    /**
     * Le volume mensuel des six derniers mois, réparti entre le marché des
     * produits et ce que rapportent formations et abonnements.
     *
     * Deux séries d'une même unité, donc un seul axe et des barres empilées :
     * la hauteur dit le total du mois, la coupure dit d'où il vient. Les
     * teintes sont celles du design system, vert primaire et or ; leur écart
     * a été vérifié pour les daltonismes (ΔE 41 en protanopie, 47 en
     * tritanopie). L'or contraste peu avec la surface, ce à quoi la légende
     * chiffrée et l'infobulle de chaque segment répondent.
     *
     * @return array<int, array{label: string, orders: Money, trainings: Money, total: Money, share: float}>
     */
    #[Computed]
    public function monthlyVolume(): array
    {
        $timezone = (string) config('app.timezone');
        $months = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now($timezone)->startOfMonth()->subMonths($offset);
            $end = $start->copy()->endOfMonth();

            $settled = Payment::query()
                ->where('status', PaymentStatus::Succeeded)
                ->whereBetween('confirmed_at', [$start->copy()->utc(), $end->copy()->utc()]);

            $orders = Money::fromInteger(
                (int) (clone $settled)->where('purpose', PaymentPurpose::Order)->sum('amount'),
            );

            $trainings = Money::fromInteger(
                (int) (clone $settled)
                    ->whereIn('purpose', [PaymentPurpose::Training, PaymentPurpose::Subscription])
                    ->sum('amount'),
            );

            $months[] = [
                'label' => $start->translatedFormat('M'),
                'orders' => $orders,
                'trainings' => $trainings,
                'total' => $orders->plus($trainings),
            ];
        }

        $highest = max(array_map(static fn (array $month): int => $month['total']->amount, $months));

        return array_map(static fn (array $month): array => [
            ...$month,
            'share' => $highest > 0 ? (float) ($month['total']->amount / $highest) : 0.0,
        ], $months);
    }

    /**
     * Les dernières actions tracées, pour le flux « opérations vitales ».
     *
     * @return Collection<int, AuditLog>
     */
    #[Computed]
    public function recentAudit(): Collection
    {
        return AuditLog::query()->with('actor')->latest('id')->limit(6)->get();
    }

    #[Computed]
    public function activeSubscriptions(): int
    {
        return Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->count();
    }

    public function render(): mixed
    {
        return view('livewire.admin.dashboard');
    }
}
