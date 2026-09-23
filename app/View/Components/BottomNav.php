<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Bottom navigation bar from the Stitch design system: five thumb-reach
 * tabs, 64px tall, white surface with a warm border, active tab in forest
 * green. Items depend on the signed-in user's role; visitors get the
 * public tabs (catalogue and trainings).
 */
class BottomNav extends Component
{
    public function __construct(public ?User $user = null) {}

    public function render(): View
    {
        // The tabs are passed as view data on purpose: an anonymous Blade
        // component template has no `$this` binding, so the template must
        // never reach back into the component instance.
        return view('components.bottom-nav', ['tabs' => $this->tabs()]);
    }

    /**
     * The tab list: five destinations like the Stitch shell, six for a farmer,
     * who has one workspace more. Icon names are Material Symbols ligatures,
     * the family the mockups use — a Heroicon name here has no ligature, so
     * the font would print the word itself.
     *
     * A label is never a single word that also names a file in `lang/fr`.
     * A dotless key sends `__()` looking for a translation group of that
     * name, and a group resolves to the whole file — an array, not a string.
     * On Windows the lookup is case-insensitive, so `__('Validation')` found
     * `validation.php` and the bar rendered an array. The test suite guards
     * this on every platform.
     *
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    public function tabs(): array
    {
        $user = $this->user;

        if ($user === null) {
            return $this->guestTabs();
        }

        if (! $user->isActive()) {
            return $this->pendingTabs();
        }

        return match ($user->role) {
            UserRole::Farmer => $this->farmerTabs(),
            UserRole::Admin => $this->adminTabs(),
            UserRole::Client => $this->clientTabs($user),
        };
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    private function guestTabs(): array
    {
        return [
            ['label' => __('Accueil'), 'href' => route('home'), 'icon' => 'home', 'current' => request()->routeIs('home'), 'badge' => null],
            ['label' => __('Catalogue'), 'href' => route('catalog.browse'), 'icon' => 'storefront', 'current' => request()->routeIs('catalog.*'), 'badge' => null],
            ['label' => __('Formations'), 'href' => route('trainings.index'), 'icon' => 'school', 'current' => request()->routeIs('trainings.*'), 'badge' => null],
            ['label' => __('Connexion'), 'href' => route('login'), 'icon' => 'person', 'current' => request()->routeIs('login'), 'badge' => null],
            ['label' => __('Inscription'), 'href' => route('register.choice'), 'icon' => 'person_add', 'current' => request()->routeIs('register*'), 'badge' => null],
        ];
    }

    /**
     * Accounts that exist but are not active yet (awaiting payment or
     * validation): no workspace tabs, the status screen is the destination.
     *
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    private function pendingTabs(): array
    {
        return [
            ['label' => __('Statut'), 'href' => route('account.status'), 'icon' => 'hourglass_top', 'current' => request()->routeIs('account.status'), 'badge' => null],
            ['label' => __('Catalogue'), 'href' => route('catalog.browse'), 'icon' => 'storefront', 'current' => request()->routeIs('catalog.*'), 'badge' => null],
            ['label' => __('Formations'), 'href' => route('trainings.index'), 'icon' => 'school', 'current' => request()->routeIs('trainings.*'), 'badge' => null],
            ['label' => __('Profil'), 'href' => route('account.overview'), 'icon' => 'person', 'current' => request()->routeIs('account.overview'), 'badge' => null],
        ];
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    private function clientTabs(User $user): array
    {
        // The five tabs of the Stitch bottom bar: Accueil, Catalogue,
        // Formations, Messages, Compte. The cart keeps its badge on the
        // header instead, like the shell's cart icon button.
        return [
            ['label' => __('Accueil'), 'href' => route('client.dashboard'), 'icon' => 'home', 'current' => request()->routeIs('client.dashboard'), 'badge' => null],
            ['label' => __('Catalogue'), 'href' => route('catalog.browse'), 'icon' => 'storefront', 'current' => request()->routeIs('catalog.*'), 'badge' => null],
            ['label' => __('Formations'), 'href' => route('client.trainings'), 'icon' => 'school', 'current' => request()->routeIs('client.trainings'), 'badge' => null],
            ['label' => __('Messages'), 'href' => route('client.messages'), 'icon' => 'chat', 'current' => request()->routeIs('client.messages*'), 'badge' => $this->unreadCount($user)],
            ['label' => __('Compte'), 'href' => route('account.overview'), 'icon' => 'person', 'current' => request()->routeIs('account.overview'), 'badge' => null],
        ];
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    private function farmerTabs(): array
    {
        return [
            ['label' => __('Accueil'), 'href' => route('farmer.dashboard'), 'icon' => 'dashboard', 'current' => request()->routeIs('farmer.dashboard'), 'badge' => null],
            ['label' => __('Produits'), 'href' => route('farmer.products'), 'icon' => 'inventory_2', 'current' => request()->routeIs('farmer.products*'), 'badge' => null],
            ['label' => __('Formations'), 'href' => route('farmer.trainings'), 'icon' => 'school', 'current' => request()->routeIs('farmer.trainings*'), 'badge' => null],
            ['label' => __('Commandes'), 'href' => route('farmer.orders'), 'icon' => 'local_shipping', 'current' => request()->routeIs('farmer.orders*'), 'badge' => null],
            ['label' => __('Messages'), 'href' => route('farmer.messages'), 'icon' => 'chat', 'current' => request()->routeIs('farmer.messages*'), 'badge' => $this->unreadCount(auth()->user())],
            ['label' => __('Compte'), 'href' => route('account.overview'), 'icon' => 'person', 'current' => request()->routeIs('account.overview'), 'badge' => null],
        ];
    }

    /**
     * @return array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>
     */
    private function adminTabs(): array
    {
        return [
            ['label' => __('Accueil'), 'href' => route('admin.dashboard'), 'icon' => 'dashboard', 'current' => request()->routeIs('admin.dashboard'), 'badge' => null],
            ['label' => __('À valider'), 'href' => route('admin.farmers'), 'icon' => 'badge', 'current' => request()->routeIs('admin.farmers'), 'badge' => null],
            ['label' => __('Utilisateurs'), 'href' => route('admin.users'), 'icon' => 'group', 'current' => request()->routeIs('admin.users'), 'badge' => null],
            ['label' => __('Modération'), 'href' => route('admin.moderation'), 'icon' => 'admin_panel_settings', 'current' => request()->routeIs('admin.moderation'), 'badge' => null],
            ['label' => __('Audit'), 'href' => route('admin.audit'), 'icon' => 'receipt_long', 'current' => request()->routeIs('admin.audit'), 'badge' => null],
        ];
    }

    private function unreadCount(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        try {
            return app(MessagingService::class)->unreadTotalFor($user) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
