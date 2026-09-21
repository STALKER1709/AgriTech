{{-- Public layout following the Stitch mobile shell: translucent sticky
     header over the warm ivory canvas, actions as pills. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stitch-surface text-stitch-ink antialiased">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-stitch-border bg-stitch-surface/90 shadow-card backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-2 px-4 lg:px-8">
                <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                        <flux:icon.leaf class="size-5" />
                    </span>
                    <span class="flex min-w-0 flex-col leading-none">
                        <span class="truncate font-display text-lg font-bold text-stitch-primary">AgriTech</span>
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

                <div class="flex items-center gap-1.5">
                    @auth
                        <flux:button size="sm" variant="primary" :href="route('dashboard')" wire:navigate class="rounded-full">
                            {{ __('Mon espace') }}
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="ghost" :href="route('login')" wire:navigate class="hidden rounded-full sm:inline-flex">
                            {{ __('Se connecter') }}
                        </flux:button>
                        <flux:button size="sm" variant="primary" :href="route('register')" wire:navigate class="rounded-full">
                            {{ __('Créer un compte') }}
                        </flux:button>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-20 lg:px-8">
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
