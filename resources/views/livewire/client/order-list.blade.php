<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Mes Commandes » --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary-soft bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.clipboard-document-list class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Mes commandes') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Historique de vos récoltes, du paiement à la livraison.') }}</p>
        </div>
    </div>

    {{-- Filtres statuts en chips pilules --}}
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <button type="button" wire:click="$set('status', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $status === ''])>
            {{ __('Toutes') }}
        </button>
        @foreach ($this->statuses() as $statusOption)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $status === $statusOption->value])>
                {{ $statusOption->label() }}
            </button>
        @endforeach
    </div>

    @forelse ($orders as $order)
        <a href="{{ route('client.orders.show', ['order' => $order->reference]) }}" wire:navigate
           class="stitch-card block p-4 transition hover:shadow-raised">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <flux:heading class="truncate">{{ $order->reference }}</flux:heading>
                        <span class="text-xs text-stitch-muted">
                            {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y') }}
                        </span>
                    </div>

                    <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-stitch-muted">
                        <flux:icon.building-storefront class="size-3.5" />
                        {{ __(':count agriculteur(s)', ['count' => $order->subOrders->count()]) }}
                        <span aria-hidden>·</span>
                        {{ $order->subOrders->pluck('items')->flatten()->count() }} {{ __('article(s)') }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-1.5">
                    @php($badgeClass = match (true) {
                        $order->status === \App\Enums\OrderStatus::Delivered => 'stitch-badge-success',
                        $order->status === \App\Enums\OrderStatus::Cancelled => 'stitch-badge-danger',
                        default => 'stitch-badge-warning',
                    })
                    <span class="{{ $badgeClass }}" data-test="order-status">{{ $order->status->label() }}</span>
                    <span class="stitch-price text-lg">{{ $order->total_amount->format() }}</span>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-2 border-t border-stitch-high pt-3">
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-stitch-primary">
                    <flux:icon.truck class="size-4" />
                    {{ __('Suivre ma commande') }}
                </span>
                <flux:icon.chevron-right class="size-4 text-stitch-muted" />
            </div>
        </a>
    @empty
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                <flux:icon.clipboard-document-list class="size-7 text-stitch-muted" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Aucune commande pour l\'instant') }}</h2>
            <p class="text-sm text-stitch-muted">{{ __('Vos achats apparaîtront ici dès votre première commande.') }}</p>
            <flux:button size="sm" variant="primary" :href="route('catalog.browse')" wire:navigate class="mt-1">
                {{ __('Voir le catalogue') }}
            </flux:button>
        </div>
    @endforelse

    {{ $orders->links() }}
</div>
