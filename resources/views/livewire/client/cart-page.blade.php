<div class="flex w-full flex-1 flex-col gap-5">
    {{-- Bandeau fidélité façon écran Stitch : progression vers un cadeau
         terroir. Objectif purement présentationnel (30 000 FCFA) : aucune
         règle métier de remise n'existe côté serveur, donc pas d'engagement
         affiché ici qui ne serait pas honoré à la caisse. --}}
    @php($goal = 30000)
    @php($totalAmount = $this->cart()->total()->amount)
    @php($progress = min(100, (int) floor($totalAmount * 100 / $goal)))
    @php($remaining = max(0, $goal - $totalAmount))
    <div class="rounded-2xl bg-stitch-low p-4 shadow-card">
        <div class="mb-2 flex items-center justify-between gap-3">
            <p class="flex min-w-0 items-center gap-2 truncate text-sm">
                <flux:icon.leaf class="size-5 shrink-0 text-stitch-terra" />
                @if ($remaining > 0)
                    {{ __('Plus que') }}
                    <span class="font-bold text-stitch-terra">{{ \App\Support\Money::fromInteger($remaining)->format() }}</span>
                    {{ __('pour un cadeau terroir !') }}
                @else
                    <span class="font-bold text-stitch-success">{{ __('Bravo, seuil cadeau terroir atteint !') }}</span>
                @endif
            </p>
            <span class="shrink-0 text-xs text-stitch-muted">{{ $progress }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-stitch-container">
            <div class="h-full rounded-full bg-stitch-terra transition-all duration-500" style="width: {{ $progress }}%;"></div>
        </div>
        <p class="mt-2 flex items-center gap-1 text-xs text-stitch-muted">
            <flux:icon.gift class="size-4 text-stitch-gold" />
            {{ __('Circuit court : la juste valeur va au producteur, sans commission cachée.') }}
        </p>
    </div>

    {{-- En-tête : titre + compteur + action vider (écran Stitch « Panier ») --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="font-display text-xl font-bold tracking-tight">{{ __('Mon panier') }}</h1>
            <span class="inline-flex items-center rounded-full bg-stitch-primary/10 px-2.5 py-0.5 text-xs font-semibold text-stitch-primary">
                {{ trans_choice('{0}0 article|{1}:count article|[2,*]:count articles', $this->cart()->items->count()) }}
            </span>
        </div>

        @if (! $this->cart()->isEmpty())
            <button
                type="button"
                wire:click="clear"
                wire:confirm="{{ __('Vider tout le panier ?') }}"
                data-test="clear-cart"
                class="inline-flex items-center gap-1 text-sm font-semibold text-stitch-muted transition hover:text-stitch-danger"
            >
                <flux:icon.trash class="size-4" />
                {{ __('Vider') }}
            </button>
        @endif
    </div>

    @forelse ($this->cart()->itemsByFarmer() as $items)
        @php($farmer = $items->first()->product->farmer)

        <div class="stitch-card overflow-hidden">
            {{-- En-tête de ferme : badge vérifié + localisation + pilule « Direct champ » --}}
            <div class="flex items-center justify-between gap-3 bg-stitch-low px-4 py-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <flux:icon.shield-check class="size-5 shrink-0 text-stitch-primary" />
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

                <span class="shrink-0 rounded-full bg-stitch-container px-2 py-0.5 text-xs font-bold text-stitch-primary">
                    {{ __('Direct champ') }}
                </span>
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
                            {{-- Stepper quantité façon Stitch : moins / champ / plus.
                                 Purement présentationnel (Alpine met à jour le champ et
                                 déclenche l'événement input que wire:model écoute) ; la
                                 valeur n'est persistée que par « Mettre à jour », qui
                                 passe par le service et ses règles de stock. --}}
                            <div class="flex h-11 items-center overflow-hidden rounded-full border border-stitch-border bg-white shadow-card"
                                 x-data="{
                                     step(delta) {
                                         const input = $root.querySelector('input');
                                         const value = parseFloat(String(input.value).replace(',', '.')) || 0;
                                         input.value = String(Math.max(0, value + delta));
                                         input.dispatchEvent(new Event('input'));
                                     },
                                 }">
                                <button type="button"
                                        x-on:click="step(-1)"
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
                                        x-on:click="step(1)"
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
                    <span class="text-xs font-semibold text-stitch-muted">
                        {{ __('Sous-total récoltes :region', ['region' => $farmer->farmerProfile?->region ?? __('Cameroun')]) }}
                    </span>
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
        <div class="stitch-card flex flex-col gap-3 p-4">
            <div class="flex items-center gap-2">
                <flux:icon.receipt-percent class="size-5 text-stitch-primary" />
                <h2 class="font-display text-sm font-bold">{{ __('Détail de la commande') }}</h2>
            </div>

            <div class="flex flex-col gap-1 text-sm text-stitch-muted">
                <div class="flex items-center justify-between py-0.5">
                    <span>{{ __('Sous-total récoltes (:count articles)', ['count' => $this->cart()->items->count()]) }}</span>
                    <span class="font-bold text-stitch-ink">{{ $this->cart()->total()->format() }}</span>
                </div>
                <div class="flex items-center justify-between py-0.5">
                    <span class="flex items-center gap-1">
                        {{ __('Frais logistique groupée') }}
                        <flux:icon.question-mark-circle class="size-3.5 text-stitch-success" />
                    </span>
                    <span class="font-bold text-stitch-success">{{ __('Offerts (0 FCFA)') }}</span>
                </div>
                <div class="my-1 h-px w-full bg-stitch-container"></div>
                <div class="flex items-baseline justify-between pt-0.5">
                    <div>
                        <span class="font-display text-sm font-bold text-stitch-ink">{{ __('Total à payer') }}</span>
                        <span class="block text-xs text-stitch-muted">{{ __('Toutes taxes comprises (TTC)') }}</span>
                    </div>
                    <span class="stitch-price text-2xl" data-test="cart-total">{{ $this->cart()->total()->format() }}</span>
                </div>
            </div>

            @unless ($this->cart()->isOrderable())
                <div class="flex items-start gap-2 rounded-xl bg-stitch-warning-soft px-3 py-2 text-xs font-medium text-stitch-warning">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                    {{ __('Corrigez les lignes signalées avant de commander : une commande est validée en une seule fois.') }}
                </div>
            @endunless

            {{-- Rassurances façon écran Stitch --}}
            <div class="flex items-start gap-3 rounded-xl bg-stitch-low p-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                    <flux:icon.shield-check class="size-5" />
                </span>
                <p class="text-xs text-stitch-muted">
                    <span class="block text-sm font-bold text-stitch-ink">{{ __('Paiement Mobile Money Garanti') }}</span>
                    {{ __('Le paiement se fait à l\'étape suivante. Votre stock n\'est réservé qu\'une fois le paiement confirmé.') }}
                </p>
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
        </div>
    @endunless
</div>
