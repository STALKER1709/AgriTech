{{-- Layout for visitors: no sidebar, and the way in is always visible. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900">
        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:brand :href="route('home')" name="AgriTech" class="max-lg:hidden dark:hidden" wire:navigate />
            <flux:brand :href="route('home')" name="AgriTech" class="max-lg:!hidden hidden dark:flex" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item :href="route('catalog.browse')" :current="request()->routeIs('catalog.*')" wire:navigate>
                    {{ __('Catalogue') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-4">
                @auth
                    <flux:button :href="route('dashboard')" size="sm" variant="primary" wire:navigate>
                        {{ __('Mon espace') }}
                    </flux:button>
                @else
                    <flux:button :href="route('login')" size="sm" variant="ghost" wire:navigate>
                        {{ __('Se connecter') }}
                    </flux:button>
                    <flux:button :href="route('register')" size="sm" variant="primary" wire:navigate>
                        {{ __('Créer un compte') }}
                    </flux:button>
                @endauth
            </flux:navbar>
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
