{{-- Authenticated shell following the Stitch design system: fixed 260px
     sidebar on desktop (like the admin web_dashboard screen), sticky top
     header on mobile, and the bottom tab bar on small screens. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stitch-surface text-stitch-ink antialiased">
        <div class="lg:pl-64">
            {{-- Top bar — always visible; on mobile it carries the menu. --}}
            <header class="fixed inset-x-0 top-0 z-40 border-b border-stitch-border bg-stitch-surface/90 shadow-card backdrop-blur-xl lg:left-64">
                <div class="flex h-16 items-center justify-between gap-2 px-4 lg:px-8">
                    <div class="flex min-w-0 items-center gap-2">
                        {{-- Mobile menu toggle (Flux sidebar collapsible) --}}
                        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                        <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                                <flux:icon.leaf class="size-4" />
                            </span>
                            <span class="hidden min-w-0 flex-col leading-none sm:flex">
                                <span class="truncate font-display text-base font-bold text-stitch-primary">AgriTech</span>
                            </span>
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:dropdown position="top" align="end">
                            <flux:profile
                                :initials="auth()->user()->initials()"
                                icon-trailing="chevron-down"
                            />

                            <flux:menu>
                                <flux:menu.radio.group>
                                    <div class="p-0 text-sm font-normal">
                                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                            <flux:avatar
                                                :name="auth()->user()->name"
                                                :initials="auth()->user()->initials()"
                                            />

                                            <div class="grid flex-1 text-start text-sm leading-tight">
                                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                            </div>
                                        </div>
                                    </div>
                                </flux:menu.radio.group>

                                <flux:menu.separator />

                                <flux:menu.radio.group>
                                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                        {{ __('Paramètres') }}
                                    </flux:menu.item>
                                    <flux:menu.item :href="route('home')" icon="building-storefront" wire:navigate>
                                        {{ __('Catalogue public') }}
                                    </flux:menu.item>
                                </flux:menu.radio.group>

                                <flux:menu.separator />

                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item
                                        as="button"
                                        type="submit"
                                        icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        {{ __('Se déconnecter') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            </header>

            {{-- Desktop sidebar: fixed, 260px, light surface like the admin shell. --}}
            <flux:sidebar class="hidden border-e border-stitch-border bg-stitch-low lg:flex lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:w-64 lg:flex-col" wire:key="sidebar">
                <flux:sidebar.header class="!bg-transparent">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </flux:sidebar.header>

                <flux:sidebar.nav class="!bg-transparent">
                    @include('layouts.navigation.' . \App\Support\RoleNavigation::partialFor(auth()->user()))
                </flux:sidebar.nav>

                <flux:spacer />

                <flux:sidebar.nav class="!bg-transparent">
                    <flux:sidebar.item icon="building-storefront" :href="route('home')" wire:navigate>
                        {{ __('Catalogue public') }}
                    </flux:sidebar.item>
                </flux:sidebar.nav>

                <div class="!bg-transparent px-3 pb-4">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:sidebar.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-sidebar"
                        >
                            {{ __('Se déconnecter') }}
                        </flux:sidebar.item>
                    </form>
                </div>
            </flux:sidebar>

            {{-- Mobile slide-over sidebar (same navigation). --}}
            <flux:sidebar class="lg:hidden" wire:key="mobile-sidebar">
                <flux:sidebar.header>
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    @include('layouts.navigation.' . \App\Support\RoleNavigation::partialFor(auth()->user()))
                </flux:sidebar.nav>
            </flux:sidebar>

            <main class="px-4 pb-24 pt-20 lg:px-8 lg:pb-10">
                {{ $slot }}
            </main>
        </div>

        <x-bottom-nav :user="auth()->user()" />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
