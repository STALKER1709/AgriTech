<div class="flex w-full flex-1 flex-col gap-5">
    {{-- Retour + référence, façon en-tête « Détail De Commande » Stitch --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('client.orders') }}" wire:navigate
               class="grid size-10 shrink-0 place-items-center rounded-full border border-stitch-border bg-white shadow-card transition hover:bg-stitch-low"
               aria-label="{{ __('Retour aux commandes') }}">
                <flux:icon.arrow-left class="size-5" />
            </a>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-bold">{{ $order->reference }}</h1>
                <p class="text-sm text-stitch-muted">
                    {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
                </p>
            </div>
        </div>

        <div class="text-end">
            <span class="stitch-price text-2xl" data-test="order-total">{{ $order->total_amount->format() }}</span>
            <div class="mt-1" data-test="order-status">
                @php($badgeClass = match (true) {
                    $order->status === \App\Enums\OrderStatus::Delivered => 'stitch-badge-success',
                    $order->status === \App\Enums\OrderStatus::Cancelled => 'stitch-badge-danger',
                    default => 'stitch-badge-warning',
                })
                <span class="{{ $badgeClass }}">{{ $order->status->label() }}</span>
            </div>
        </div>
    </div>

    {{-- Paiement : carte choix opérateur façon « Choix du moyen de paiement » --}}
    @if ($this->isAwaitingPayment())
        <div class="stitch-card flex flex-col gap-4 p-4 sm:p-5">
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-full bg-stitch-low">
                    <flux:icon.lock-closed class="size-4 text-stitch-primary" />
                </span>
                <div>
                    <h2 class="font-display text-sm font-bold">{{ __('Payer la commande') }}</h2>
                    <p class="text-xs text-stitch-muted">
                        {{ __('Paiement Mobile Money simulé. Aucun opérateur réel n\'est contacté.') }}
                    </p>
                </div>
            </div>

            @if ($order->expires_at)
                <div class="flex items-center gap-2 rounded-xl bg-stitch-warning-soft px-3 py-2 text-xs font-medium text-stitch-warning">
                    <flux:icon.clock class="size-4 shrink-0" />
                    {{ __('À payer avant le :date, sans quoi la commande sera annulée.', [
                        'date' => $order->expires_at->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i'),
                    ]) }}
                </div>
            @endif

            @if ($this->paymentInFlight())
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-stitch-primary/5 px-3 py-2.5">
                    <span class="flex items-center gap-2 text-xs font-medium text-stitch-primary">
                        <flux:icon.arrow-path class="size-4" />
                        {{ __('Un paiement est déjà en cours de vérification pour cette commande.') }}
                    </span>
                    <flux:button size="sm" variant="primary"
                                 :href="route('payments.pending', ['payment' => $this->paymentInFlight()->provider_reference])"
                                 wire:navigate>
                        {{ __('Suivre le paiement') }}
                    </flux:button>
                </div>
            @endif

            <form wire:submit="pay" class="flex flex-col gap-4">
                <div>
                    <flux:label>{{ __('Opérateur') }}</flux:label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($this->methods() as $methodOption)
                            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 bg-white p-3 shadow-card transition
                                          {{ $method === $methodOption->value ? 'border-stitch-primary ring-2 ring-stitch-primary/15' : 'border-stitch-border hover:border-stitch-high' }}">
                                <input type="radio" value="{{ $methodOption->value }}" wire:model="method" class="sr-only" data-test="method" />

                                @if ($methodOption->value === 'mtn_momo')
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-stitch-mtn text-[10px] font-black text-black">MTN</span>
                                @else
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-stitch-orange text-[10px] font-black text-white">OM</span>
                                @endif

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold">{{ $methodOption->label() }}</span>
                                    <span class="block text-xs text-stitch-muted">*126# / #150#</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                <flux:button type="submit" variant="primary" icon="credit-card" wire:loading.attr="disabled" data-test="pay">
                    {{ __('Payer :amount', ['amount' => $order->total_amount->format()]) }}
                </flux:button>
            </form>
        </div>
    @endif

    {{-- Sous-commandes : une carte par ferme, comme le panier --}}
    @foreach ($order->subOrders as $subOrder)
        <div class="stitch-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 bg-stitch-low px-4 py-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white">
                        <flux:icon.building-storefront class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">
                            {{ $subOrder->farmer->farmerProfile?->farm_name ?? $subOrder->farmer->name }}
                        </p>
                        <p class="truncate text-xs text-stitch-muted">{{ $subOrder->reference }}</p>
                    </div>
                </div>

                <span class="{{ $subOrder->status === \App\Enums\SubOrderStatus::Delivered ? 'stitch-badge-success' : 'stitch-badge-warning' }} shrink-0">
                    {{ $subOrder->status->label() }}
                </span>
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

                <div class="flex items-center justify-between gap-3 bg-stitch-low/60 px-4 py-2.5">
                    <span class="text-xs font-semibold text-stitch-muted">{{ __('Sous-total') }}</span>
                    <span class="text-sm font-bold">{{ $subOrder->subtotal_amount->format() }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
