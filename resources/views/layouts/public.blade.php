{{-- Public layout following the Stitch mobile shell exactly: fixed 64px
     translucent header with the two-line brand block, round 44px icon
     actions (search, cart with terracotta badge) and the connection pill.
     The bottom tab bar below mirrors nav of the Stitch screens. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stitch-surface text-stitch-ink antialiased">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-stitch-border bg-stitch-surface/90 shadow-[0_1px_8px_rgba(31,36,33,0.04)] backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-2 px-4 lg:px-8">
                {{-- Brand : logo + two-line wordmark, as drawn on every screen. --}}
                <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                        <flux:icon.leaf class="size-5" />
                    </span>
                    <span class="flex min-w-0 flex-col leading-none">
                        <span class="truncate font-display text-base font-bold text-stitch-primary">AgriTech</span>
                        <span class="truncate text-xs text-stitch-muted">{{ __('Le carrefour agricole du Cameroun') }}</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-1 lg:flex">
                    <a href="{{ route('catalog.browse') }}" wire:navigate
                       class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('catalog.*') ? 'bg-stitch-primary/10 text-stitch-primary' : 'text-stitch-muted hover:bg-white hover:text-stitch-ink' }}">
                        {{ __('Catalogue') }}
                    </a>
                    <a href="{{ route('trainings.index') }}" wire:navigate
                       class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('trainings.*') ? 'bg-stitch-primary/10 text-stitch-primary' : 'text-stitch-muted hover:bg-white hover:text-stitch-ink' }}">
                        {{ __('Formations') }}
                    </a>
                </nav>

                <div class="flex items-center gap-1">
                    {{-- Round 44px icon actions from the Stitch header. --}}
                    <a href="{{ route('catalog.browse') }}" wire:navigate
                       class="grid size-11 place-items-center rounded-full text-stitch-ink transition-colors hover:bg-stitch-high/70"
                       aria-label="{{ __('Rechercher') }}">
                        <flux:icon.magnifying-glass class="size-5" />
                    </a>

                    @auth
                        <a href="{{ route('client.cart') }}" wire:navigate
                           class="relative grid size-11 place-items-center rounded-full text-stitch-ink transition-colors hover:bg-stitch-high/70"
                           aria-label="{{ __('Panier') }}">
                            <flux:icon.shopping-cart class="size-5" />
                            @php($cartCount = auth()->user()?->cartItemCount() ?? 0)
                            @if ($cartCount > 0)
                                <span class="absolute right-1.5 top-1.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-stitch-terra px-1 text-[10px] font-bold leading-tight text-white">
                                    {{ $cartCount > 9 ? '9+' : $cartCount }}
                                </span>
                            @endif
                        </a>

                        <flux:button size="sm" variant="primary" :href="route('dashboard')" wire:navigate class="rounded-full">
                            {{ __('Mon espace') }}
                        </flux:button>
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="hidden h-8 items-center justify-center rounded-full bg-stitch-primary/10 px-3 text-sm font-semibold text-stitch-primary transition-colors hover:bg-stitch-primary/20 sm:inline-flex">
                            {{ __('Connexion') }}
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                           class="inline-flex h-10 items-center justify-center rounded-full bg-stitch-primary px-4 text-sm font-bold text-white shadow-card transition-colors hover:bg-stitch-primary-dark sm:inline-flex">
                            {{ __('Créer un compte') }}
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 pb-24 pt-20 lg:px-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-stitch-border bg-white/60 py-6 text-center text-xs text-stitch-muted">
            {{ __('Cameroun • Paiements Mobile Money simulés (MTN MoMo, Orange Money)') }}
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
