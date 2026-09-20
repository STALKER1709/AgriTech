{{-- Simple auth layout following the Stitch login screen: centered card on
     the warm ivory canvas with the brand chip above the form. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-stitch-surface text-stitch-ink antialiased">
        <header class="fixed inset-x-0 top-0 z-50 border-b border-stitch-border bg-stitch-surface/90 shadow-card backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-5xl items-center justify-between px-4">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                        <flux:icon.leaf class="size-5" />
                    </span>
                    <span class="font-display text-lg font-bold text-stitch-primary">AgriTech</span>
                </a>

                <span class="inline-flex items-center gap-1.5 rounded-full bg-stitch-primary/10 px-3 py-1 text-xs font-semibold text-stitch-primary">
                    <flux:icon.leaf class="size-3.5" />
                    {{ __('Le carrefour agricole du Cameroun') }}
                </span>
            </div>
        </header>

        <main class="flex w-full flex-1 flex-col px-4 pb-16 pt-24">
            <div class="mx-auto w-full max-w-md">
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
