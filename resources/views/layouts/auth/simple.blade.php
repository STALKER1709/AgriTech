{{-- Coquille des écrans d'authentification, reprise de `agritech_connexion` :
     canevas ivoire, halos décoratifs, barre de marque fixe, puis une carte
     centrée. Le contenu vit dans `$slot`. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-surface text-text-primary antialiased">
        {{-- Halos ambiants de la maquette. Purement décoratifs, donc hors
             de l'arbre d'accessibilité. --}}
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <span class="absolute -left-24 -top-24 h-64 w-64 rounded-full bg-primary-fixed/50 blur-3xl"></span>
            <span class="absolute -right-20 top-1/3 h-56 w-56 rounded-full bg-tertiary-fixed/40 blur-3xl"></span>
            <span class="absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-secondary-fixed/40 blur-3xl"></span>
        </div>

        <header class="fixed inset-x-0 top-0 z-50 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(31,36,33,0.04)]"
                style="padding-top: env(safe-area-inset-top, 0px);">
            <div class="mx-auto flex h-16 w-full max-w-5xl items-center justify-between gap-space-sm px-margin">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5 min-w-0">
                    <span class="flex w-9 h-9 items-center justify-center rounded-full bg-primary text-on-primary shadow-card shrink-0">
                        <x-icon name="eco" size="20" filled />
                    </span>
                    <span class="font-headline-sm text-headline-sm text-primary tracking-tight truncate">AgriTech</span>
                </a>

                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1 font-label-sm text-label-sm text-primary">
                    <x-icon name="eco" size="14" />
                    {{ __('Le carrefour agricole du Cameroun') }}
                </span>
            </div>
        </header>

        <main class="relative z-10 flex w-full flex-1 flex-col px-margin pb-space-2xl pt-24">
            <div class="mx-auto w-full {{ $wide ?? false ? 'max-w-2xl' : 'max-w-md' }}">
                {{ $slot }}
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
