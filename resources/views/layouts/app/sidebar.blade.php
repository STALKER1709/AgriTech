{{--
    Coquille des espaces connectés, reprise des maquettes Stitch.

    Deux formes, comme les maquettes : en dessous de `lg`, la coquille mobile
    (`mobile_tab`) — en-tête fixe de 64 px, contenu entre 64 px et la barre
    d'onglets, barre d'onglets basse de 64 px. À partir de `lg`, la coquille
    web (`web_dashboard`) — rail latéral fixe de 256 px sur
    `surface-container-low`, en-tête fixe décalé de 256 px, contenu dans la
    gouttière de 24 px.

    Les maquettes client et agriculteur n'existent qu'en mobile : la version
    web réutilise le vocabulaire du back-office, seul écran large fourni.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-surface font-body-md text-body-md text-text-primary antialiased min-h-screen">
        {{-- Rail latéral — web uniquement --}}
        <aside class="hidden lg:flex fixed left-0 top-0 h-full w-64 bg-surface-container-low shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex-col justify-between">
            <div class="flex flex-col flex-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="h-16 px-space-md flex items-center gap-space-sm bg-surface-container-low shrink-0">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <x-icon name="eco" size="18" filled class="text-on-primary" />
                    </span>
                    <div class="flex flex-col min-w-0">
                        <span class="font-headline-sm text-headline-sm text-text-primary tracking-tight leading-none truncate">AgriTech</span>
                        <span class="font-label-sm text-label-sm text-text-secondary truncate mt-1">
                            {{ \App\Support\RoleNavigation::spaceLabel(auth()->user()) }}
                        </span>
                    </div>
                </a>

                <div class="px-space-md py-space-xs">
                    <div class="px-space-sm py-1.5 rounded-lg bg-surface-container flex items-center justify-between">
                        <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Région Cameroun') }}</span>
                        <span class="inline-flex items-center gap-1 font-label-sm text-label-sm font-semibold text-primary">
                            <span class="w-2 h-2 rounded-full bg-status-success"></span>+237
                        </span>
                    </div>
                </div>

                <nav class="flex-1 px-space-sm py-space-sm space-y-1" aria-label="{{ __('Navigation principale') }}">
                    @include('layouts.navigation.' . \App\Support\RoleNavigation::partialFor(auth()->user()))
                </nav>

                <div class="px-space-sm pb-space-sm">
                    <x-nav-item icon="storefront" :href="route('home')" :current="false">
                        {{ __('Catalogue public') }}
                    </x-nav-item>
                </div>
            </div>

            {{-- Carte utilisateur en pied de rail (maquette back-office) --}}
            <div class="p-space-sm bg-surface-container-low">
                <div class="p-space-sm rounded-xl bg-surface-container flex items-center justify-between gap-space-xs">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <div class="relative shrink-0">
                            <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-headline-sm text-[14px]">
                                {{ auth()->user()->initials() }}
                            </div>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-status-success ring-2 ring-surface-white"></span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-lg text-label-lg text-text-primary font-semibold truncate">{{ auth()->user()->name }}</span>
                            <a href="{{ route('profile.edit') }}" wire:navigate
                               class="font-label-sm text-label-sm text-text-secondary truncate hover:text-primary transition-colors">
                                {{ __('Mon compte') }}
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" data-test="logout-sidebar" title="{{ __('Se déconnecter') }}"
                                class="p-1.5 rounded-lg text-text-secondary hover:bg-surface-container-high hover:text-error transition-colors shrink-0 cursor-pointer">
                            <x-icon name="logout" size="20" />
                            <span class="sr-only">{{ __('Se déconnecter') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="lg:pl-64 flex flex-col min-h-screen">
            <x-shell-header />

            {{-- `pt-16` dégage l'en-tête fixe, `pb-24` la barre d'onglets. Les
                 marges verticales du contenu vivent dans le conteneur interne :
                 une classe `py-*` sur le même élément écraserait `pt-16`. --}}
            <main class="w-full flex-1 bg-surface pt-16 pb-24 px-margin lg:pb-space-2xl lg:px-space-lg">
                <div class="py-space-md lg:py-space-lg">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <x-bottom-nav :user="auth()->user()" />

        {{-- Tiroir de navigation mobile : les maquettes s'appuient sur la barre
             d'onglets, qui ne porte que cinq destinations. Les rôles qui en ont
             davantage gardent ce tiroir, ouvert depuis l'en-tête. --}}
        <div x-data="{ open: false }" x-on:open-drawer.window="open = true" x-cloak class="lg:hidden">
            <div x-show="open" x-transition.opacity class="fixed inset-0 z-[60] bg-inverse-surface/40" x-on:click="open = false"></div>

            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 z-[61] w-72 bg-surface-container-low shadow-[0_12px_28px_rgba(31,36,33,0.12)] flex flex-col">
                <div class="h-16 px-space-md flex items-center justify-between">
                    <span class="font-headline-sm text-headline-sm text-text-primary">AgriTech</span>
                    <button type="button" x-on:click="open = false" class="p-2 rounded-full text-text-secondary hover:bg-surface-container">
                        <x-icon name="close" size="22" />
                        <span class="sr-only">{{ __('Fermer') }}</span>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-space-sm py-space-sm space-y-1">
                    @include('layouts.navigation.' . \App\Support\RoleNavigation::partialFor(auth()->user()))

                    <x-nav-item icon="storefront" :href="route('home')" :current="false">
                        {{ __('Catalogue public') }}
                    </x-nav-item>
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="p-space-sm">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-space-sm py-2.5 rounded-lg text-text-secondary hover:bg-surface-container hover:text-error transition-colors cursor-pointer">
                        <x-icon name="logout" size="20" />
                        <span class="font-label-lg text-label-lg">{{ __('Se déconnecter') }}</span>
                    </button>
                </form>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
