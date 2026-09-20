<div class="flex w-full flex-1 flex-col gap-5">
    {{-- Salutation façon écran Stitch « Tableau de bord Vendeur » --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">
                {{ __('Bonjour :name', ['name' => auth()->user()?->first_name]) }}
            </h1>
            <p class="text-sm text-stitch-muted">
                {{ auth()->user()?->farmerProfile?->farm_name ?? __('Votre exploitation') }}
                @if (auth()->user()?->isActive())
                    <span class="stitch-badge-success ml-1">{{ __('Certifié') }} ✓</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Wallet : carte vert forêt avec solde + raccourcis création --}}
    <div class="flex flex-col gap-4 rounded-2xl bg-stitch-primary p-5 text-white shadow-raised sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-full bg-white/15">
                    <flux:icon.wallet class="size-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-white/70">{{ __('Solde disponible') }}</p>
                    <p class="stitch-price text-2xl !text-white">{{ $this->earnedTotal->minus($this->commissionTotal)->format() }}</p>
                    <p class="text-xs text-white/70">
                        {{ __('CA :amount · commission :commission', [
                            'amount' => $this->earnedTotal->format(),
                            'commission' => $this->commissionTotal->format(),
                        ]) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('farmer.products.create') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-bold text-stitch-primary shadow-card transition hover:bg-white/90">
                <flux:icon.plus class="size-4" />
                {{ __('Produit') }}
            </a>
            <a href="{{ route('farmer.trainings.create') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/25">
                <flux:icon.video-camera class="size-4" />
                {{ __('Formation') }}
            </a>
            <a href="{{ route('farmer.orders') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/25">
                <flux:icon.truck class="size-4" />
                {{ __('Commandes') }}
            </a>
        </div>
    </div>

    {{-- KPI bento : ventes, catalogue, formations, messages --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stitch-card flex flex-col gap-1 p-4">
            <span class="flex size-9 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.chart-bar class="size-4" />
            </span>
            <p class="mt-1 text-xs font-medium text-stitch-muted">{{ __('Chiffre d\'affaires payé') }}</p>
            <p class="stitch-price text-xl">{{ $this->earnedTotal->format() }}</p>
            <p class="text-xs text-stitch-muted">
                {{ __('dont :amount de commission plateforme', ['amount' => $this->commissionTotal->format()]) }}
            </p>
        </div>

        <div class="stitch-card flex flex-col gap-1 p-4">
            <span class="flex size-9 items-center justify-center rounded-full bg-stitch-warning-soft text-stitch-warning">
                <flux:icon.queue-list class="size-4" />
            </span>
            <p class="mt-1 text-xs font-medium text-stitch-muted">{{ __('À préparer') }}</p>
            <p class="stitch-price text-xl">{{ $this->toPrepare }}</p>
            <p class="text-xs text-stitch-muted">{{ __('sous-commandes en attente') }}</p>
        </div>

        <div class="stitch-card flex flex-col gap-1 p-4">
            <span class="flex size-9 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                <flux:icon.squares-plus class="size-4" />
            </span>
            <p class="mt-1 text-xs font-medium text-stitch-muted">{{ __('Catalogue') }}</p>
            <p class="stitch-price text-xl">{{ $this->publishedProducts }}</p>
            <p class="text-xs text-stitch-muted">
                {{ trans_choice('{0}produit publié|{1}:count produit publié|[2,*]:count produits publiés', $this->publishedProducts) }}
                · {{ $this->publishedTrainings }} {{ __('formation(s)') }}
            </p>
        </div>

        <div class="stitch-card flex flex-col gap-1 p-4">
            <span class="flex size-9 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.chat-bubble-left-right class="size-4" />
            </span>
            <p class="mt-1 text-xs font-medium text-stitch-muted">{{ __('Messages non lus') }}</p>
            <p class="stitch-price text-xl">{{ $this->unreadMessages }}</p>
            <p class="text-xs text-stitch-muted">{{ __('de vos clients') }}</p>
        </div>
    </div>

    {{-- File de travail : à préparer maintenant --}}
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wide text-stitch-muted">{{ __('À préparer maintenant') }}</h2>
            <flux:button size="sm" variant="ghost" :href="route('farmer.orders')" wire:navigate>
                {{ __('Toutes les commandes') }}
            </flux:button>
        </div>

        @if ($this->pendingWork()->isEmpty())
            <div class="flex items-center gap-3 rounded-xl border border-stitch-border bg-stitch-success-soft px-4 py-3">
                <flux:icon.check-circle class="size-5 shrink-0 text-stitch-success" />
                <p class="text-sm font-medium text-stitch-success">{{ __('Rien à préparer pour l\'instant. Profitez-en !') }}</p>
            </div>
        @else
            <div class="flex flex-col gap-2.5">
                @foreach ($this->pendingWork() as $subOrder)
                    <div class="stitch-card flex flex-wrap items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-display text-sm font-bold">
                                {{ $subOrder->reference }} — {{ $subOrder->order->client->name }}
                            </p>
                            <p class="mt-0.5 truncate text-sm text-stitch-muted">
                                {{ $subOrder->items->map(fn ($item) => $item->product->name)->implode(', ') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="stitch-price text-base">{{ $subOrder->subtotal_amount->format() }}</span>
                            <flux:button size="sm" variant="primary" :href="route('farmer.orders')" wire:navigate>
                                {{ __('Préparer') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
