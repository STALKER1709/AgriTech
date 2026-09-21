<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête : titre + compteur + action vider (écran Stitch « Panier ») --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.shopping-bag class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold">{{ __('Mon panier') }}</h1>
                <p class="text-sm text-stitch-muted">
                    {{ __(':count article(s) — regroupés par ferme', ['count' => $this->cart()->items->count()]) }}
                </p>
            </div>
        </div>

        @if (! $this->cart()->isEmpty())
            <button
                type="button"
                wire:click="clear"
                wire:confirm="{{ __('Vider tout le panier ?') }}"
                data-test="clear-cart"
                class="inline-flex items-center gap-1.5 rounded-full border border-stitch-border bg-white px-3.5 py-2 text-sm font-semibold text-stitch-danger shadow-card transition hover:bg-stitch-danger-soft"
            >
                <flux:icon.trash class="size-4" />
                {{ __('Vider') }}
            </button>
        @endif
    </div>

    @forelse ($this->cart()->itemsByFarmer() as $items)
        @php($farmer = $items->first()->product->farmer)

        <div class="stitch-card overflow-hidden">
            {{-- En-tête de ferme : pastille verte + localisation, comme l'écran Stitch --}}
            <div class="flex items-center justify-between gap-3 bg-stitch-low px-4 py-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white">
                        <flux:icon.building-storefront class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">
                            {{ $farmer->farmerProfile?->farm_name ?? $farmer->name }}
                        </p>
                        <p class="flex items-center gap-1 truncate text-xs text-stitch-muted">
                            <flux:icon.map-pin class="size-3" />
                            {{ $farmer->farmerProfile?->region ?? __('Cameroun') }}
                        </p>
                    </div>
                </div>

                <flux:text class="shrink-0 text-xs font-semibold text-stitch-muted">
                    {{ __(':count produit(s)', ['count' => $items->count()]) }}
                </flux:text>
            </div>

            <div class="divide-y divide-stitch-high">
                @foreach ($items as $item)
                    <div class="flex flex-col gap-3 p-4">
                        <div class="flex min-w-0 gap-3">
                            <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-stitch-container">
                                @if ($item->product->images->isNotEmpty())
                                    <img src="{{ $item->product->images->first()->url() }}" alt="" class="size-full object-cover" />
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('catalog.product', ['product' => $item->product->slug]) }}" wire:navigate
                                   class="block truncate font-display text-sm font-bold hover:text-stitch-primary">
                                    {{ $item->product->name }}
                                </a>
                                <p class="mt-0.5 text-xs text-stitch-muted">
                                    {{ $item->product->unit_price->format() }} / {{ $item->product->unit->shortLabel() }}
                                </p>
                                <p class="stitch-price mt-1 text-base">{{ $item->lineTotal()->format() }}</p>
                            </div>
                        </div>

                        @unless ($item->isOrderable())
                            <div class="flex items-start gap-2 rounded-xl bg-stitch-warning-soft px-3 py-2 text-xs font-medium text-stitch-warning">
                                <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                                {{ $item->unavailableReason() }}
                            </div>
                        @endunless

                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Stepper quantité façon Stitch : moins / champ / plus --}}
                            <div class="flex h-11 items-center overflow-hidden rounded-full border border-stitch-border bg-white shadow-card">
                                <button type="button"
                                        wire:click="updateQuantity({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        data-test="quantity-decrement-{{ $item->id }}"
                                        class="grid h-full w-10 place-items-center text-stitch-muted transition hover:text-stitch-primary">
                                    −
                                </button>

                                <flux:input
                                    wire:model="quantities.{{ $item->id }}"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-16 [&_input]:!border-x [&_input]:!rounded-none [&_input]:border-stitch-border [&_input]:text-center [&_input]:font-bold"
                                    data-test="quantity-{{ $item->id }}"
                                />

                                <button type="button"
                                        x-data="{ step() { const input = this.$root.parentElement.querySelector('input'); input.value = (parseFloat(String(input.value).replace(',', '.')) || 0) + 1; input.dispatchEvent(new Event('input')); } }"
                                        x-on:click="step()"
                                        class="grid h-full w-10 place-items-center text-stitch-muted transition hover:text-stitch-primary">
                                    +
                                </button>
                            </div>

                            <button type="button"
                                    wire:click="updateQuantity({{ $item->id }})"
                                    class="rounded-full px-3 py-2 text-xs font-semibold text-stitch-primary transition hover:bg-stitch-primary/10"
                                    data-test="update-{{ $item->id }}">
                                {{ __('Mettre à jour') }}
                            </button>

                            <button type="button"
                                    wire:click="remove({{ $item->id }})"
                                    class="ml-auto inline-flex items-center gap-1 rounded-full px-3 py-2 text-xs font-semibold text-stitch-muted transition hover:bg-stitch-danger-soft hover:text-stitch-danger"
                                    data-test="remove-{{ $item->id }}">
                                <flux:icon.x-mark class="size-3.5" />
                                {{ __('Retirer') }}
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- Sous-total du groupe de ferme --}}
                <div class="flex items-center justify-between gap-3 bg-stitch-low/60 px-4 py-2.5">
                    <span class="text-xs font-semibold text-stitch-muted">{{ __('Sous-total ferme') }}</span>
                    <span class="text-sm font-bold">{{ \App\Support\Money::fromInteger($items->sum(fn ($i) => $i->lineTotal()->amount))->format() }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                <flux:icon.shopping-bag class="size-7 text-stitch-muted" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Votre panier est vide') }}</h2>
            <p class="text-sm text-stitch-muted">{{ __('Parcourez le catalogue pour y ajouter des produits.') }}</p>
            <flux:button size="sm" variant="primary" :href="route('catalog.browse')" wire:navigate class="mt-1">
                {{ __('Voir le catalogue') }}
            </flux:button>
        </div>
    @endforelse

    {{-- Récapitulatif façon carte « Détail de la commande » Stitch --}}
    @unless ($this->cart()->isEmpty())
        <div class="stitch-card sticky bottom-24 z-30 flex flex-col gap-3 p-4 lg:bottom-6">
            <div class="flex items-center gap-2">
                <flux:icon.receipt-percent class="size-5 text-stitch-terra" />
                <h2 class="font-display text-sm font-bold">{{ __('Détail de la commande') }}</h2>
            </div>

            @unless ($this->cart()->isOrderable())
                <div class="flex items-start gap-2 rounded-xl bg-stitch-warning-soft px-3 py-2 text-xs font-medium text-stitch-warning">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                    {{ __('Corrigez les lignes signalées avant de commander : une commande est validée en une seule fois.') }}
                </div>
            @endunless

            <div class="flex items-end justify-between gap-3">
                <span class="text-sm text-stitch-muted">{{ __('Total à payer') }}</span>
                <span class="stitch-price text-2xl" data-test="cart-total">{{ $this->cart()->total()->format() }}</span>
            </div>

            <flux:button
                variant="primary"
                icon="credit-card"
                wire:click="placeOrder"
                wire:loading.attr="disabled"
                :disabled="! $this->cart()->isOrderable()"
                data-test="place-order">
                {{ __('Commander') }}
            </flux:button>

            <p class="text-center text-xs text-stitch-muted">
                {{ __('Le paiement se fait à l\'étape suivante. Votre stock n\'est réservé qu\'une fois le paiement confirmé.') }}
            </p>
        </div>
    @endunless
</div>
