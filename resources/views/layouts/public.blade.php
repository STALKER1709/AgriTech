{{--
    Coquille publique, reprise de l'en-tête des maquettes `mobile_tab`
    (`agritech_catalogue_produits`) : 64 px, ivoire translucide, marque sur
    deux lignes à gauche, actions rondes de 44 px à droite, pastille de compte
    de 32 px. La barre d'onglets basse est la même que dans les espaces
    connectés — les maquettes la montrent aussi pour un visiteur.

    Au-delà de `lg`, les maquettes ne disent rien du public : on élargit dans
    le même vocabulaire, avec les liens de section dans l'en-tête et un
    contenu borné à 1200 px comme le prévoit DESIGN.md.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-surface font-body-md text-body-md text-on-surface antialiased flex flex-col min-h-screen">
        <header class="fixed top-0 inset-x-0 z-50 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(31,36,33,0.04)]"
                style="padding-top: env(safe-area-inset-top, 0px);">
            <div class="mx-auto flex h-16 w-full max-w-[1200px] items-center justify-between gap-space-sm px-gutter lg:px-margin-desktop">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-space-sm min-w-0">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <x-icon name="eco" size="18" filled class="text-on-primary" />
                    </span>
                    <div class="flex flex-col min-w-0">
                        <span class="font-headline-sm text-headline-sm text-primary leading-none truncate">AgriTech</span>
                        <span class="font-label-sm text-label-sm text-text-secondary truncate">
                            {{ __('Le carrefour agricole du Cameroun') }}
                        </span>
                    </div>
                </a>

                <nav class="hidden items-center gap-1 lg:flex">
                    <a href="{{ route('catalog.browse') }}" wire:navigate
                       @class([
                           'rounded-full px-4 h-9 inline-flex items-center font-label-lg text-label-lg transition-colors',
                           'bg-primary/10 text-primary' => request()->routeIs('catalog.*'),
                           'text-text-secondary hover:bg-surface-container hover:text-text-primary' => ! request()->routeIs('catalog.*'),
                       ])>{{ __('Catalogue') }}</a>

                    <a href="{{ route('trainings.index') }}" wire:navigate
                       @class([
                           'rounded-full px-4 h-9 inline-flex items-center font-label-lg text-label-lg transition-colors',
                           'bg-primary/10 text-primary' => request()->routeIs('trainings.*'),
                           'text-text-secondary hover:bg-surface-container hover:text-text-primary' => ! request()->routeIs('trainings.*'),
                       ])>{{ __('Formations') }}</a>
                </nav>

                <div class="flex items-center gap-space-xs shrink-0">
                    <a href="{{ route('catalog.browse') }}" wire:navigate aria-label="{{ __('Recherche') }}"
                       class="w-11 h-11 flex items-center justify-center rounded-full text-on-surface hover:bg-surface-container transition-colors">
                        <x-icon name="search" size="22" />
                    </a>

                    @auth
                        @php($cartCount = auth()->user()?->isClient() ? auth()->user()->cartItemCount() : 0)
                        <a href="{{ auth()->user()?->isClient() ? route('client.cart') : route('dashboard') }}" wire:navigate
                           aria-label="{{ __('Panier') }}"
                           class="relative w-11 h-11 flex items-center justify-center rounded-full text-on-surface hover:bg-surface-container transition-colors">
                            <x-icon name="shopping_cart" size="22" />
                            @if ($cartCount > 0)
                                <span class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] px-1 bg-secondary text-on-secondary font-label-sm text-[10px] leading-tight font-bold rounded-full flex items-center justify-center">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('dashboard') }}" wire:navigate aria-label="{{ __('Mon espace') }}"
                           class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                            <x-icon name="person" size="18" class="text-on-primary" />
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="hidden sm:inline-flex items-center justify-center px-3 h-8 rounded-full bg-primary/10 text-primary font-label-lg text-label-lg hover:bg-primary/20 transition-colors">
                            {{ __('Connexion') }}
                        </a>

                        <a href="{{ route('register.choice') }}" wire:navigate aria-label="{{ __('Créer un compte') }}"
                           class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                            <x-icon name="person" size="18" class="text-on-primary" />
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1200px] flex-1 px-gutter lg:px-margin-desktop pt-16 pb-24 lg:pb-space-2xl">
            {{ $slot }}
        </main>

        <footer class="border-t border-border-warm bg-surface-container-lowest/60 py-space-lg pb-24 lg:pb-space-lg text-center font-label-sm text-label-sm text-text-secondary">
            <p>{{ __('Cameroun • Paiements Mobile Money simulés (MTN MoMo, Orange Money)') }}</p>

            <a href="{{ route('credits.photos') }}" wire:navigate class="mt-1 inline-block text-primary hover:underline">
                {{ __('Crédits photographiques') }}
            </a>
        </footer>

        <x-bottom-nav :user="auth()->user()" />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
