{{--
    En-tête fixe, dans ses deux formes.

    Mobile (`mobile_tab`) : 64 px, ivoire translucide, marque à gauche, actions
    à droite. La pastille ronde verte de 32 px ouvre le tiroir de navigation —
    les maquettes n'ont pas de bouton menu, et c'est l'endroit où l'on cherche
    son compte.

    Web (`web_dashboard`) : 64 px décalé de 256 px, champ de recherche en
    pilule, notifications, pastille de compte.
--}}
@php
    $user = auth()->user();
    $cartCount = $user?->isClient() ? $user->cartItemCount() : 0;
    // La cloche compte les notifications non lues, pas les messages : les
    // deux existent, et un message non lu produit déjà sa notification.
    $unreadNotifications = $user?->unreadNotifications()->count() ?? 0;
@endphp

<header class="fixed top-0 inset-x-0 lg:left-64 z-40 h-16 bg-surface/90 lg:bg-surface-white/95 backdrop-blur-xl shadow-[0_1px_8px_rgba(31,36,33,0.04)]"
        style="padding-top: env(safe-area-inset-top, 0px);">
    <div class="h-16 px-gutter lg:px-space-lg flex items-center justify-between gap-space-sm">
        {{-- Marque (mobile) / recherche (web) --}}
        <div class="flex items-center gap-space-sm min-w-0 lg:flex-1 lg:max-w-2xl">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-space-sm min-w-0 lg:hidden">
                <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                    <x-icon name="eco" size="18" filled class="text-on-primary" />
                </span>
                <div class="flex flex-col min-w-0">
                    <span class="font-headline-sm text-headline-sm text-primary leading-none truncate">AgriTech</span>
                    <span class="font-label-sm text-label-sm text-text-secondary truncate">
                        {{ \App\Support\RoleNavigation::spaceLabel($user) }}
                    </span>
                </div>
            </a>

            <form action="{{ route('catalog.browse') }}" method="GET" class="relative hidden lg:block w-full max-w-md">
                <x-icon name="search" size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary" />
                <input type="search" name="search"
                       class="w-full h-10 pl-10 pr-space-md rounded-full bg-surface-container-low text-text-primary font-body-md text-[14px] placeholder:text-text-secondary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                       placeholder="{{ __('Rechercher un producteur, un produit, une formation…') }}" />
            </form>

            <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-container-low text-text-secondary font-label-sm text-label-sm">
                <span class="w-2 h-2 rounded-full bg-status-success"></span>
                <span>{{ __('Paiement Mobile Money simulé') }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-space-xs lg:gap-space-md shrink-0">
            <div class="hidden md:flex items-center px-3 py-1 rounded-full bg-surface-container-low font-label-sm text-label-sm font-semibold text-text-secondary">
                FCFA (XAF)
            </div>

            @if ($user?->isClient())
                <a href="{{ route('client.cart') }}" wire:navigate aria-label="{{ __('Panier') }}"
                   class="relative w-11 h-11 flex items-center justify-center rounded-full text-on-surface hover:bg-surface-container transition-colors">
                    <x-icon name="shopping_cart" size="22" />
                    @if ($cartCount > 0)
                        <span class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] px-1 bg-secondary text-on-secondary font-label-sm text-[10px] leading-tight font-bold rounded-full flex items-center justify-center">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    @endif
                </a>
            @endif

            @if ($user !== null)
                <a href="{{ route('notifications') }}" wire:navigate aria-label="{{ __('Notifications') }}"
                   class="relative p-2 rounded-full text-text-secondary hover:bg-surface-container-low hover:text-text-primary transition-colors">
                    <x-icon name="notifications" size="22" />
                    @if ($unreadNotifications > 0)
                        <span class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] px-1 bg-payment-orange text-on-primary font-label-sm text-[10px] leading-tight font-bold rounded-full flex items-center justify-center ring-2 ring-surface-white">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- Mobile : ouvre le tiroir. Web : menu de compte. --}}
            <button type="button" x-on:click="$dispatch('open-drawer')" aria-label="{{ __('Mon compte') }}"
                    class="lg:hidden w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0 cursor-pointer">
                <x-icon name="person" size="18" class="text-on-primary" />
            </button>

            <flux:dropdown position="bottom" align="end" class="hidden lg:block">
                <button type="button" aria-label="{{ __('Mon compte') }}"
                        class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0 cursor-pointer">
                    <span class="font-headline-sm text-[13px] text-on-primary">{{ $user?->initials() }}</span>
                </button>

                <flux:menu>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Paramètres') }}
                    </flux:menu.item>
                    <flux:menu.item :href="route('home')" icon="building-storefront" wire:navigate>
                        {{ __('Catalogue public') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Se déconnecter') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</header>
