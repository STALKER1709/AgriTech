{{--
    Reproduction de `agritech_mes_commandes` : barre d'action avec recherche
    escamotable, contrôle segmenté « En cours / Terminées » compté, carrousel
    de pastilles de statut, puis une carte par commande barrée d'un dégradé.

    Écarts assumés, tous du même principe : n'afficher que ce que le serveur
    sait. La maquette annonce une livraison estimée, un bouton « Suivre
    livraison », un poids estimé et un « Recommander en 1 clic » — la
    plateforme ne transporte rien, ne pèse rien et ne rejoue pas un panier.
    Ces emplacements gardent leur forme et changent de contenu : le nombre
    réel de producteurs et d'articles, et un unique bouton « Détails ».
    Le pavé d'assistance WhatsApp devient la messagerie, qui, elle, existe.
--}}
<div class="flex w-full flex-1 flex-col" x-data="{ searching: @js($search !== '') }">
    {{-- Barre d'action et recherche --}}
    <section class="pt-space-sm pb-space-xs flex flex-col">
        <div class="flex items-center justify-between gap-space-sm mb-space-sm">
            <div class="flex items-center gap-space-xs min-w-0">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-fixed text-primary shrink-0">
                    <x-icon name="receipt_long" size="18" />
                </span>
                <h1 class="font-body-md-bold text-body-md-bold text-text-primary truncate">
                    {{ __('Historique de vos récoltes') }}
                </h1>
            </div>

            <button type="button" x-on:click="searching = ! searching"
                    :aria-expanded="searching ? 'true' : 'false'"
                    aria-label="{{ __('Rechercher une commande') }}"
                    class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-text-secondary hover:bg-surface-container-high transition-colors shrink-0">
                <x-icon name="search" size="20" />
            </button>
        </div>

        <div x-show="searching" x-cloak class="mb-space-sm">
            <div class="relative flex items-center w-full">
                <span class="absolute left-3 text-text-secondary pointer-events-none">
                    <x-icon name="search" size="20" />
                </span>
                <input type="search" wire:model.live.debounce.400ms="search"
                       x-init="$watch('searching', value => value && $nextTick(() => $el.focus()))"
                       placeholder="{{ __('Rechercher par référence (CMD-…) ou produit') }}"
                       class="w-full h-11 pl-10 pr-4 rounded-xl bg-surface-container-lowest text-text-primary font-body-md text-body-md placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary shadow-card" />
            </div>
        </div>

        {{-- Contrôle segmenté --}}
        <div class="flex p-1 rounded-full bg-surface-container shadow-card mb-space-sm" role="tablist">
            @foreach (['open' => __('En cours'), 'closed' => __('Terminées')] as $key => $label)
                @php($current = $tab === $key)
                <button type="button" role="tab" aria-selected="{{ $current ? 'true' : 'false' }}"
                        wire:click="$set('tab', '{{ $key }}')"
                        @class([
                            'flex-1 py-2 rounded-full font-label-lg text-label-lg flex items-center justify-center gap-space-xs transition-all',
                            'text-on-primary bg-primary shadow-card' => $current,
                            'text-text-secondary hover:text-text-primary' => ! $current,
                        ])>
                    <span>{{ $label }}</span>
                    <span @class([
                        'w-5 h-5 rounded-full font-label-sm text-label-sm flex items-center justify-center',
                        'bg-primary-container text-on-primary' => $current,
                        'bg-surface-container-high text-text-secondary' => ! $current,
                    ])>{{ $key === 'open' ? $this->openCount() : $this->closedCount() }}</span>
                </button>
            @endforeach
        </div>

        {{-- Pastilles de statut --}}
        <div class="flex items-center gap-space-xs overflow-x-auto pb-space-xs pt-1 no-scrollbar">
            <button type="button" wire:click="$set('status', '')"
                    @class([
                        'shrink-0 px-3.5 py-1.5 rounded-full font-label-sm text-label-sm shadow-card transition-all',
                        'bg-primary text-on-primary' => $status === '',
                        'bg-surface-container-lowest text-text-secondary hover:bg-surface-container-high' => $status !== '',
                    ])>
                {{ __('Toutes') }}
            </button>

            @foreach ($this->statuses() as $statusOption)
                @php($current = $status === $statusOption->value)
                <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                        @class([
                            'shrink-0 px-3.5 py-1.5 rounded-full font-label-sm text-label-sm shadow-card transition-all',
                            'bg-primary text-on-primary' => $current,
                            'bg-surface-container-lowest text-text-secondary hover:bg-surface-container-high' => ! $current,
                        ])>
                    {{ $statusOption->label() }}
                </button>
            @endforeach
        </div>
    </section>

    {{-- Fil des commandes --}}
    <section class="flex flex-col gap-space-md mt-space-xs" wire:loading.class="opacity-60" wire:target="tab,status,search">
        @forelse ($orders as $order)
            {{-- Trois directives en ligne plutôt qu'un bloc : Blade extrait
                 les blocs bruts avec une expression non gourmande partant de
                 la première directive PHP du fichier — ici une directive en
                 ligne, plus haut. Un bloc ici avalerait tout ce qui les
                 sépare, et la page ne rendrait plus que du texte. --}}
            @php($badge = match ($order->status) {
                    \App\Enums\OrderStatus::Preparing => ['bg-secondary-fixed text-on-secondary-fixed-variant', 'local_shipping', 'bg-secondary'],
                    \App\Enums\OrderStatus::Paid => ['bg-primary-fixed text-on-primary-fixed-variant', 'check_circle', 'bg-primary'],
                    \App\Enums\OrderStatus::Delivered => ['bg-surface-container-high text-text-secondary', 'task_alt', 'bg-outline'],
                    \App\Enums\OrderStatus::Cancelled => ['bg-[#ffebee] text-status-error', 'cancel', 'bg-status-error'],
                    \App\Enums\OrderStatus::PendingPayment => ['bg-[#fff3e0] text-status-warning', 'schedule', 'bg-tertiary-fixed-dim'],
            })
            @php($method = $this->paidWith($order))
            @php($thumbnails = $this->thumbnails($order))

            <article class="relative rounded-2xl bg-surface-container-lowest p-space-md shadow-card overflow-hidden flex flex-col gap-space-sm">
                @if ($order->status === \App\Enums\OrderStatus::Preparing)
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-secondary to-tertiary-fixed-dim"></div>
                @endif

                <div @class(['flex items-start justify-between gap-space-xs', 'pt-1' => $order->status === \App\Enums\OrderStatus::Preparing])>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-headline-sm text-headline-sm text-text-primary">{{ $order->reference }}</span>
                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $badge[2] }}"></span>
                            <span class="font-label-sm text-label-sm text-text-secondary shrink-0">
                                {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <p class="font-label-sm text-label-sm text-text-secondary mt-0.5 truncate">
                            {{ $order->subOrders->map(fn ($subOrder) => $subOrder->farmer->farmerProfile?->farm_name ?? $subOrder->farmer->name)->join(' · ') }}
                        </p>
                    </div>

                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-label-sm text-label-sm shrink-0 {{ $badge[0] }}"
                          data-test="order-status">
                        <x-icon :name="$badge[1]" size="16" />
                        <span>{{ $order->status->label() }}</span>
                    </span>
                </div>

                <p class="font-body-md text-body-md text-text-primary line-clamp-2">{{ $this->summarise($order) }}</p>

                {{-- Collage de vignettes. La maquette y met un poids estimé ;
                     la plateforme ne pèse rien, donc on y met ce qu'elle
                     connaît : les producteurs et le nombre d'articles. --}}
                <div class="flex items-center gap-space-xs py-1">
                    @foreach ($thumbnails as $item)
                        <div class="relative w-16 h-16 rounded-xl overflow-hidden bg-surface-container-high shrink-0 shadow-card">
                            <img src="{{ $item->product->images->first()->url() }}" alt="{{ $item->product->name }}"
                                 loading="lazy" class="w-full h-full object-cover" />
                            {{-- La maquette écrit « x2 » : seule la quantité,
                                 l'unité tenant déjà dans la ligne au-dessus. --}}
                            <span class="absolute bottom-1 right-1 bg-text-primary/80 text-surface font-label-sm text-[10px] px-1 rounded whitespace-nowrap">
                                ×{{ $item->quantity->format() }}
                            </span>
                        </div>
                    @endforeach

                    <div class="flex-1 h-16 rounded-xl bg-surface-container-low px-3 flex flex-col justify-center min-w-0">
                        <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                            <x-icon name="agriculture" size="15" class="text-primary" />
                            {{ trans_choice(':count producteur|:count producteurs', $this->farmerCount($order), ['count' => $this->farmerCount($order)]) }}
                        </span>
                        <span class="font-label-sm text-label-sm text-text-primary font-semibold">
                            {{ trans_choice(':count article|:count articles', $order->subOrders->sum(fn ($subOrder) => $subOrder->items->count()), ['count' => $order->subOrders->sum(fn ($subOrder) => $subOrder->items->count())]) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-space-sm pt-1">
                    <div class="min-w-0">
                        <span class="font-label-sm text-label-sm text-text-secondary block">
                            {{ $method ? __('Total payé') : __('Montant') }}
                        </span>
                        <span class="font-price-tag text-price-tag text-primary" data-test="order-total">
                            {{ $order->total_amount->format() }}
                        </span>
                    </div>

                    @if ($method)
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-high shrink-0">
                            <span @class([
                                'w-3.5 h-3.5 rounded-full shadow-card inline-block',
                                'bg-payment-mtn' => $method === \App\Enums\PaymentMethod::MtnMomo,
                                'bg-payment-orange' => $method === \App\Enums\PaymentMethod::OrangeMoney,
                            ])></span>
                            <span class="font-label-sm text-label-sm text-text-primary font-semibold">{{ $method->label() }}</span>
                        </div>
                    @endif
                </div>

                <a href="{{ route('client.orders.show', ['order' => $order->reference]) }}" wire:navigate
                   class="h-12 px-3 lg:px-8 lg:self-end rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-1 hover:bg-primary-container transition-all shadow-card">
                    <span>{{ $order->status === \App\Enums\OrderStatus::PendingPayment ? __('Payer la commande') : __('Détails') }}</span>
                    <x-icon name="arrow_forward" size="18" />
                </a>
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="receipt_long" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">
                    {{ $status !== '' || $search !== ''
                        ? __('Aucune commande ne correspond')
                        : ($tab === 'closed' ? __('Aucune commande terminée') : __('Aucune commande en cours')) }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary">
                    {{ $status !== '' || $search !== ''
                        ? __('Essayez un autre statut, ou une autre référence.')
                        : __('Vos achats apparaîtront ici dès votre première commande.') }}
                </p>

                @if ($status !== '' || $search !== '')
                    <button type="button" wire:click="resetFilters"
                            class="mt-1 h-12 px-6 rounded-full bg-surface-container-low text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                        <x-icon name="filter_alt_off" size="18" />
                        {{ __('Effacer les filtres') }}
                    </button>
                @else
                    <a href="{{ route('catalog.browse') }}" wire:navigate
                       class="mt-1 h-12 px-6 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-primary-container transition-colors shadow-card">
                        <x-icon name="storefront" size="18" />
                        {{ __('Voir le catalogue') }}
                    </a>
                @endif
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="pt-space-xs">{{ $orders->links() }}</div>
        @endif
    </section>

    {{-- Pavé d'assistance. La maquette propose un numéro WhatsApp et promet
         une réponse en quinze minutes : ni le numéro ni le délai n'existent.
         La messagerie interne, elle, existe — c'est elle qu'on propose. --}}
    <section class="mt-space-lg mb-space-md">
        <div class="rounded-2xl bg-surface-container-low p-space-md shadow-card flex items-start gap-space-sm relative overflow-hidden">
            <span class="absolute -right-3 -bottom-3 text-surface-container-high/40 select-none pointer-events-none" aria-hidden="true">
                <x-icon name="forum" size="88" />
            </span>

            <div class="w-11 h-11 rounded-full bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-card">
                <x-icon name="chat" size="24" />
            </div>

            <div class="flex-1 pr-2 min-w-0">
                <h2 class="font-body-md-bold text-body-md-bold text-text-primary">{{ __('Une question sur une commande ?') }}</h2>
                <p class="font-label-sm text-label-sm text-text-secondary mt-0.5">
                    {{ __('Écrivez directement à l\'agriculteur concerné depuis la messagerie.') }}
                </p>
                <a href="{{ route('client.messages') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 mt-2.5 px-4 py-2 rounded-full bg-primary-fixed text-on-primary-fixed font-label-lg text-label-lg shadow-card hover:bg-primary-fixed-dim transition-colors">
                    <x-icon name="mail" size="18" />
                    <span>{{ __('Ouvrir la messagerie') }}</span>
                </a>
            </div>
        </div>
    </section>
</div>
