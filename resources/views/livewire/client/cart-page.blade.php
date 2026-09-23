<div class="flex w-full flex-col gap-space-md pb-28 lg:pb-24">
    {{-- En-tête — `agritech_panier` --}}
    <div class="flex items-center justify-between gap-space-sm">
        <div class="flex items-center gap-space-sm min-w-0">
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ __('Mon panier') }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm shrink-0">
                {{ trans_choice('{0}Vide|{1}:count article|[2,*]:count articles', $this->cart()->itemCount()) }}
            </span>
        </div>

        @unless ($this->cart()->isEmpty())
            <button type="button" wire:click="clear" wire:confirm="{{ __('Retirer tous les articles de votre panier ?') }}"
                    data-test="clear-cart"
                    class="flex items-center gap-1 font-label-lg text-label-lg text-text-secondary hover:text-status-error transition-colors active:scale-95 shrink-0 cursor-pointer">
                <x-icon name="delete_sweep" size="18" />
                <span>{{ __('Vider') }}</span>
            </button>
        @endunless
    </div>

    @forelse ($this->cart()->itemsByFarmer() as $items)
        @php($farmer = $items->first()->product->farmer)
        @php($profile = $farmer->farmerProfile)
        @php($subtotal = \App\Support\Money::sum($items->map(fn ($item) => $item->lineTotal())))

        {{-- Un groupe par producteur : c'est aussi le découpage de la commande --}}
        <section class="bg-surface-container-lowest rounded-xl shadow-card overflow-hidden">
            <div class="bg-surface-container-low px-space-md py-space-sm flex items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-xs min-w-0">
                    <x-icon name="verified" size="20" filled class="text-primary" />
                    <div class="min-w-0">
                        <h2 class="font-headline-sm text-headline-sm text-text-primary truncate">
                            {{ $profile?->farm_name ?? $farmer->name }}
                        </h2>
                        @if ($profile)
                            <p class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                                <x-icon name="location_on" size="14" />
                                {{ collect([$profile->city, $profile->region])->filter()->join(', ') }}
                            </p>
                        @endif
                    </div>
                </div>

                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm font-body-md-bold shrink-0">
                    {{ __('Direct champ') }}
                </span>
            </div>

            <div class="p-space-md flex flex-col gap-space-md">
                @foreach ($items as $item)
                    <article class="flex gap-space-sm items-start relative" wire:key="line-{{ $item->id }}">
                        <div class="w-20 h-20 rounded-lg overflow-hidden shrink-0 bg-surface-container">
                            @if ($item->product->images->isNotEmpty())
                                <img src="{{ $item->product->images->first()->url() }}" alt="" loading="lazy"
                                     class="w-full h-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <x-icon name="photo_camera" size="22" class="text-surface-container-highest" />
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 flex flex-col justify-between min-h-20 gap-space-xs">
                            <div class="flex justify-between items-start gap-space-xs">
                                <div class="min-w-0">
                                    <h3 class="font-body-md-bold text-body-md-bold text-text-primary leading-tight truncate">
                                        <a href="{{ route('catalog.product', ['product' => $item->product->slug]) }}" wire:navigate>
                                            {{ $item->product->name }}
                                        </a>
                                    </h3>
                                    <p class="font-label-sm text-label-sm text-text-secondary">
                                        {{ $item->quantity->format() }} {{ $item->product->unit->countLabel($item->quantity) }}
                                        ({{ $item->product->unit_price->format() }} / {{ $item->product->unit->shortLabel() }})
                                    </p>
                                </div>

                                <button type="button" wire:click="remove({{ $item->id }})"
                                        aria-label="{{ __('Retirer cet article') }}" data-test="remove-{{ $item->id }}"
                                        class="text-text-secondary hover:text-status-error transition-colors p-1 -mr-1 shrink-0 cursor-pointer">
                                    <x-icon name="close" size="18" />
                                </button>
                            </div>

                            @unless ($item->isOrderable())
                                <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-warning">
                                    <x-icon name="error" size="14" />
                                    {{ $item->unavailableReason() }}
                                </p>
                            @endunless

                            <div class="flex items-center justify-between gap-space-xs mt-auto">
                                <div class="flex items-center bg-surface-container rounded-full px-1 py-0.5">
                                    <button type="button" wire:click="decrement({{ $item->id }})"
                                            aria-label="{{ __('Diminuer') }}" data-test="minus-{{ $item->id }}"
                                            class="w-7 h-7 rounded-full flex items-center justify-center text-text-primary hover:bg-surface-container-high transition-colors active:scale-90 cursor-pointer">
                                        <x-icon name="remove" size="16" />
                                    </button>

                                    <input type="text" wire:model.live.debounce.500ms="quantities.{{ $item->id }}"
                                           wire:change="updateQuantity({{ $item->id }})"
                                           aria-label="{{ __('Quantité') }}" data-test="quantity-{{ $item->id }}"
                                           class="font-body-md-bold text-body-md-bold text-text-primary w-10 text-center tabular-nums bg-transparent focus:outline-none" />

                                    <button type="button" wire:click="increment({{ $item->id }})"
                                            aria-label="{{ __('Augmenter') }}" data-test="plus-{{ $item->id }}"
                                            class="w-7 h-7 rounded-full flex items-center justify-center text-text-primary hover:bg-surface-container-high transition-colors active:scale-90 cursor-pointer">
                                        <x-icon name="add" size="16" />
                                    </button>
                                </div>

                                <span class="font-price-tag text-price-tag text-primary">{{ $item->lineTotal()->format() }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="bg-surface-container-low/60 px-space-md py-space-xs flex items-center justify-between gap-space-sm">
                <span class="font-label-sm text-label-sm text-text-secondary">
                    {{ __('Sous-total :farm', ['farm' => $profile?->farm_name ?? $farmer->name]) }}
                </span>
                <span class="font-body-md-bold text-body-md-bold text-text-primary">{{ $subtotal->format() }}</span>
            </div>
        </section>
    @empty
        <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-card flex flex-col items-center gap-space-sm text-center">
            <span class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center">
                <x-icon name="shopping_cart" size="28" class="text-text-secondary" />
            </span>
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre panier est vide') }}</h2>
            <p class="font-body-md text-body-md text-text-secondary">
                {{ __('Parcourez le catalogue pour y ajouter des produits.') }}
            </p>
            <x-button :href="route('catalog.browse')" icon="storefront">{{ __('Voir le catalogue') }}</x-button>
        </div>
    @endforelse

    @unless ($this->cart()->isEmpty())
        {{-- Récapitulatif. La maquette y ajoute des frais logistiques offerts,
             une remise circuit court et une mention TTC : la plateforme ne
             connaît ni livraison, ni remise, ni taxe. Ce qu'elle sait, elle
             l'affiche ; le reste n'a pas sa place dans un total à payer. --}}
        <section class="bg-surface-container-lowest rounded-xl p-space-md shadow-card">
            <h2 class="font-headline-sm text-headline-sm text-text-primary mb-space-sm flex items-center gap-space-xs">
                <x-icon name="receipt_long" size="20" class="text-primary" />
                {{ __('Détail de la commande') }}
            </h2>

            <div class="flex flex-col gap-space-xs font-body-md text-body-md text-text-secondary">
                @foreach ($this->cart()->itemsByFarmer() as $items)
                    @php($farmer = $items->first()->product->farmer)
                    <div class="flex justify-between items-center gap-space-sm py-1">
                        <span class="min-w-0 truncate">
                            {{ $farmer->farmerProfile?->farm_name ?? $farmer->name }}
                            <span class="text-text-secondary/70">
                                ({{ trans_choice('{1}:count article|[2,*]:count articles', $items->count()) }})
                            </span>
                        </span>
                        <span class="text-text-primary font-body-md-bold shrink-0">
                            {{ \App\Support\Money::sum($items->map(fn ($item) => $item->lineTotal()))->format() }}
                        </span>
                    </div>
                @endforeach

                <div class="my-space-xs h-px bg-surface-container w-full"></div>

                <div class="flex justify-between items-baseline pt-1 gap-space-sm">
                    <div>
                        <span class="font-headline-sm text-headline-sm text-text-primary">{{ __('Total à payer') }}</span>
                        <span class="block font-label-sm text-label-sm text-text-secondary">
                            {{ __('Réglé en une fois, réparti entre les producteurs') }}
                        </span>
                    </div>
                    <span class="font-headline-lg-mobile text-headline-lg-mobile text-primary shrink-0" data-test="cart-total">
                        {{ $this->cart()->total()->format() }}
                    </span>
                </div>
            </div>
        </section>

        @unless ($this->cart()->isOrderable())
            <div class="p-space-md rounded-xl bg-[#fff3e0] flex items-start gap-3">
                <x-icon name="error" size="22" class="text-status-warning shrink-0" />
                <p class="font-body-md text-body-md text-text-primary leading-relaxed">
                    {{ __('Corrigez les lignes signalées avant de commander : une commande est validée en une seule fois.') }}
                </p>
            </div>
        @endunless

        {{-- Bandeau de réassurance : les deux affirmations sont vraies. --}}
        <section class="bg-surface-container-low rounded-xl p-space-md flex flex-col gap-space-sm">
            <div class="flex items-center gap-space-sm">
                <div class="w-10 h-10 rounded-full bg-payment-mtn/20 flex items-center justify-center shrink-0">
                    <x-icon name="verified_user" size="22" class="text-secondary" />
                </div>
                <div class="min-w-0">
                    <h3 class="font-body-md-bold text-body-md-bold text-text-primary">{{ __('Paiement Mobile Money') }}</h3>
                    <p class="font-label-sm text-label-sm text-text-secondary">
                        {{ __('MTN MoMo et Orange Money. Le stock n\'est décompté qu\'une fois le paiement confirmé par l\'opérateur.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-space-sm">
                <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                    <x-icon name="handshake" size="22" class="text-primary" />
                </div>
                <div class="min-w-0">
                    <h3 class="font-body-md-bold text-body-md-bold text-text-primary">{{ __('Direct producteur') }}</h3>
                    <p class="font-label-sm text-label-sm text-text-secondary">
                        {{ __('Votre commande est répartie entre les exploitations : chacune prépare sa part.') }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Barre de commande fixe, au-dessus de la barre d'onglets --}}
        <aside class="fixed bottom-16 lg:bottom-0 inset-x-0 z-40 px-gutter lg:px-space-lg py-2 bg-surface/90 backdrop-blur-md shadow-float"
               style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom, 0px));">
            <div class="max-w-md lg:max-w-2xl mx-auto flex items-center justify-between gap-space-sm bg-surface-container-lowest p-space-sm rounded-full shadow-raised">
                <div class="pl-space-sm min-w-0">
                    <span class="font-label-sm text-label-sm text-text-secondary block leading-none">{{ __('Total') }}</span>
                    <span class="font-headline-sm text-headline-sm text-primary tracking-tight">{{ $this->cart()->total()->format() }}</span>
                </div>

                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                        @disabled(! $this->cart()->isOrderable()) data-test="place-order"
                        class="h-12 px-space-lg rounded-full bg-primary hover:bg-primary-container text-on-primary font-body-md-bold text-body-md-bold flex items-center justify-center gap-space-xs active:scale-95 transition-all shadow-raised disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shrink-0">
                    <span>{{ __('Commander') }}</span>
                    <x-icon name="arrow_forward" size="18" />
                </button>
            </div>
        </aside>
    @endunless
</div>
