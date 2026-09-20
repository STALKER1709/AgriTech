<x-layouts::app :title="__('Espace client')">
    <div class="flex w-full flex-1 flex-col gap-5">
        {{-- En-tête façon écran « Mon Compte » Stitch --}}
        <div class="stitch-card flex flex-wrap items-center gap-4 p-4 sm:p-5">
            <span class="grid size-14 shrink-0 place-items-center rounded-full bg-stitch-primary font-display text-lg font-bold text-white">
                {{ auth()->user()?->initials() }}
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-lg font-bold">{{ auth()->user()?->name }}</h1>
                    @if (auth()->user()?->isActive())
                        <span class="stitch-badge-success">{{ __('Client vérifié') }}</span>
                    @endif
                </div>
                <p class="truncate text-sm text-stitch-muted">{{ auth()->user()?->phone }}</p>
            </div>

            <flux:button size="sm" variant="ghost" :href="route('profile.edit')" wire:navigate class="shrink-0">
                {{ __('Modifier mon profil') }}
            </flux:button>
        </div>

        {{-- Mes activités : cartes avec chevron, façon liste « Mes activités » --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('client.orders') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                    <flux:icon.shopping-bag class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Mes commandes') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">{{ __('Suivi des livraisons en cours') }}</span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>

            <a href="{{ route('client.cart') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                    <flux:icon.shopping-cart class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Mon panier') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">
                        {{ __(':count produit(s) en attente.', ['count' => auth()->user()?->cartItemCount() ?? 0]) }}
                    </span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>

            <a href="{{ route('client.trainings') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                    <flux:icon.academic-cap class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Mes formations') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">{{ __('Vos achats et votre Pass') }}</span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>

            <a href="{{ route('client.subscriptions') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-gold-soft text-[#8d6b00]">
                    <flux:icon.trophy class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Abonnement') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">{{ __('Accès illimité aux formations incluses.') }}</span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>

            <a href="{{ route('client.messages') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                    <flux:icon.chat-bubble-left-right class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Messagerie') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">{{ __('Vos échanges avec les agriculteurs.') }}</span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>

            <a href="{{ route('trainings.index') }}" wire:navigate class="stitch-card group flex items-center gap-3 p-4 transition hover:shadow-raised">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                    <flux:icon.play-circle class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ __('Catalogue formations') }}</span>
                    <span class="block truncate text-xs text-stitch-muted">{{ __('Apprenez auprès des agriculteurs eux-mêmes.') }}</span>
                </span>
                <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted transition group-hover:translate-x-0.5" />
            </a>
        </div>
    </div>
</x-layouts::app>
