<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Mon panier') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Vos produits sont regroupés par agriculteur : chacun prépare sa part.') }}</flux:text>
        </div>

        @if (! $this->cart()->isEmpty())
            <flux:button variant="ghost" wire:click="clear" wire:confirm="{{ __('Vider tout le panier ?') }}" data-test="clear-cart">
                {{ __('Vider le panier') }}
            </flux:button>
        @endif
    </div>

    @forelse ($this->cart()->itemsByFarmer() as $items)
        @php($farmer = $items->first()->product->farmer)

        <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading size="sm">{{ $farmer->farmerProfile?->farm_name ?? $farmer->name }}</flux:heading>
                <flux:text class="text-sm">
                    {{ __(':count produit(s)', ['count' => $items->count()]) }}
                </flux:text>
            </div>

            @foreach ($items as $item)
                <div class="flex flex-col gap-3 border-t border-neutral-100 pt-3 dark:border-neutral-800">
                    <div class="flex min-w-0 gap-3">
                        <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @if ($item->product->images->isNotEmpty())
                                <img src="{{ $item->product->images->first()->url() }}" alt="" class="size-full object-cover" />
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <flux:heading class="truncate">
                                <a href="{{ route('catalog.product', ['product' => $item->product->slug]) }}" wire:navigate>
                                    {{ $item->product->name }}
                                </a>
                            </flux:heading>
                            <flux:text class="mt-1 text-sm">
                                {{ $item->product->unit_price->format() }} / {{ $item->product->unit->shortLabel() }}
                            </flux:text>
                            <flux:heading size="sm" class="mt-1">{{ $item->lineTotal()->format() }}</flux:heading>
                        </div>
                    </div>

                    @unless ($item->isOrderable())
                        <flux:callout icon="exclamation-triangle" variant="warning">
                            <flux:callout.text>{{ $item->unavailableReason() }}</flux:callout.text>
                        </flux:callout>
                    @endunless

                    <div class="flex flex-wrap items-end gap-2">
                        <flux:input
                            wire:model="quantities.{{ $item->id }}"
                            :label="__('Quantité (:unit)', ['unit' => $item->product->unit->shortLabel()])"
                            class="w-28"
                            data-test="quantity-{{ $item->id }}" />

                        <flux:button size="sm" wire:click="updateQuantity({{ $item->id }})" data-test="update-{{ $item->id }}">
                            {{ __('Mettre à jour') }}
                        </flux:button>

                        <flux:button size="sm" variant="ghost" wire:click="remove({{ $item->id }})" data-test="remove-{{ $item->id }}">
                            {{ __('Retirer') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <flux:callout icon="shopping-cart">
            <flux:callout.heading>{{ __('Votre panier est vide') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Parcourez le catalogue pour y ajouter des produits.') }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="primary" :href="route('catalog.browse')" wire:navigate>
                    {{ __('Voir le catalogue') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endforelse

    @unless ($this->cart()->isEmpty())
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="sm">{{ __('Total') }}</flux:heading>
                <flux:heading size="lg" data-test="cart-total">{{ $this->cart()->total()->format() }}</flux:heading>
            </div>

            @unless ($this->cart()->isOrderable())
                <flux:callout icon="exclamation-triangle" variant="warning">
                    <flux:callout.text>
                        {{ __('Corrigez les lignes signalées avant de commander : une commande est validée en une seule fois.') }}
                    </flux:callout.text>
                </flux:callout>
            @endunless

            <flux:button
                variant="primary"
                icon="credit-card"
                wire:click="placeOrder"
                wire:loading.attr="disabled"
                :disabled="! $this->cart()->isOrderable()"
                data-test="place-order">
                {{ __('Commander') }}
            </flux:button>

            <flux:text class="text-sm">
                {{ __('Le paiement se fait à l\'étape suivante. Votre stock n\'est réservé qu\'une fois le paiement confirmé.') }}
            </flux:text>
        </div>
    @endunless
</div>
