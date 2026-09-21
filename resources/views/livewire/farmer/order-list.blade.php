<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon file de travail du dashboard vendeur Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.truck class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Commandes reçues') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Les commandes payées, à préparer puis à livrer.') }}</p>
        </div>
    </div>

    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <button type="button" wire:click="$set('status', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $status === ''])>
            {{ __('Tous') }}
        </button>
        @foreach ($this->statuses() as $statusOption)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $status === $statusOption->value])>
                {{ $statusOption->label() }}
            </button>
        @endforeach
    </div>

    @forelse ($subOrders as $subOrder)
        <div class="stitch-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 bg-stitch-low px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate font-display text-sm font-bold">{{ $subOrder->reference }}</p>
                    <p class="truncate text-xs text-stitch-muted">
                        {{ __('Client : :name', ['name' => $subOrder->order->client->name]) }}
                        · {{ $subOrder->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
                    </p>
                </div>

                @php($badge = match ($subOrder->status) {
                    \App\Enums\SubOrderStatus::Delivered => 'stitch-badge-success',
                    \App\Enums\SubOrderStatus::Cancelled => 'stitch-badge-danger',
                    default => 'stitch-badge-warning',
                })
                <span class="{{ $badge }} shrink-0">{{ $subOrder->status->label() }}</span>
            </div>

            <div class="divide-y divide-stitch-high">
                @foreach ($subOrder->items as $item)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-2.5">
                        <span class="min-w-0 flex-1 truncate text-sm">
                            {{ $item->product->name }}
                            <span class="text-stitch-muted">
                                × {{ $item->quantity->format() }} {{ $item->product->unit->shortLabel() }}
                            </span>
                        </span>
                        <span class="text-sm font-semibold">{{ $item->line_total->format() }}</span>
                    </div>
                @endforeach

                {{-- Finances : sous-total, commission, net agriculteur --}}
                <div class="flex flex-col gap-1 bg-stitch-low/60 px-4 py-3">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-stitch-muted">{{ __('Sous-total') }}</span>
                        <span class="font-semibold">{{ $subOrder->subtotal_amount->format() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-stitch-muted">
                            {{ __('Commission (:rate %)', ['rate' => $subOrder->commission_rate_snapshot]) }}
                        </span>
                        <span class="font-semibold text-stitch-danger">− {{ $subOrder->commission_amount->format() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-bold">{{ __('Ce qui vous revient') }}</span>
                        <span class="stitch-price text-lg" data-test="payout-{{ $subOrder->id }}">
                            {{ $subOrder->farmerPayout()->format() }}
                        </span>
                    </div>
                </div>
            </div>

            @if ($subOrder->status !== \App\Enums\SubOrderStatus::Cancelled)
                <div class="flex flex-wrap gap-2 px-4 py-3">
                    @can('prepare', $subOrder)
                        <flux:button size="sm" variant="primary" wire:click="prepare({{ $subOrder->id }})" data-test="prepare-{{ $subOrder->id }}">
                            {{ __('Passer en préparation') }}
                        </flux:button>
                    @endcan

                    @can('deliver', $subOrder)
                        <flux:button size="sm" wire:click="deliver({{ $subOrder->id }})" data-test="deliver-{{ $subOrder->id }}">
                            {{ __('Marquer comme livrée') }}
                        </flux:button>
                    @endcan
                </div>
            @endif
        </div>
    @empty
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                <flux:icon.truck class="size-7 text-stitch-muted" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Aucune commande reçue') }}</h2>
            <p class="max-w-xs text-sm text-stitch-muted">
                {{ __('Une commande apparaît ici dès que son paiement est confirmé.') }}
            </p>
        </div>
    @endforelse

    {{ $subOrders->links() }}
</div>
