{{--
    Reproduction de `agritech_tableau_de_bord_vendeur` : salutation, carte de
    solde vert forêt avec ses raccourcis, grille de quatre indicateurs,
    graphique des ventes hebdomadaires, puis la file de travail.

    Écarts : la maquette propose un sélecteur de période, un virement « vers
    MTN MoMo » et une comparaison au mois précédent. Aucun virement n'existe
    — la plateforme ne verse rien, elle enregistre — et rien ne conserve les
    totaux d'un mois passé pour les comparer. Le graphique, lui, reste :
    quatre semaines de sous-commandes réellement payées.
--}}
@php($farmer = auth()->user())
@php($profile = $farmer?->farmerProfile)

<div class="flex w-full flex-1 flex-col gap-space-md">
    {{-- Salutation --}}
    <section class="flex items-center gap-space-sm min-w-0">
        <span class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center font-headline-sm text-primary shrink-0">
            {{ $farmer?->initials() }}
        </span>

        <div class="min-w-0">
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight truncate">
                {{ __('Bonjour :name', ['name' => $farmer?->first_name]) }}
            </h1>
            <p class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1 truncate">
                <x-icon name="agriculture" size="15" class="text-secondary shrink-0" />
                {{ $profile?->farm_name ?? __('Votre exploitation') }}
                @if ($profile?->isValidated())
                    <x-icon name="verified" size="15" filled class="text-primary shrink-0" />
                @endif
            </p>
        </div>
    </section>

    {{-- Solde --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#165a31] to-[#0d3f20] text-on-primary p-space-md shadow-raised flex flex-col gap-space-md">
        <span class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-tertiary-fixed/10 pointer-events-none" aria-hidden="true"></span>

        <div class="relative z-10 flex items-start gap-space-sm">
            <span class="w-10 h-10 rounded-full bg-on-primary/15 flex items-center justify-center shrink-0">
                <x-icon name="account_balance_wallet" size="20" />
            </span>

            <div class="min-w-0">
                <span class="font-label-sm text-label-sm text-on-primary/75 uppercase tracking-wide block">
                    {{ __('Revenu net encaissé') }}
                </span>
                <span class="font-headline-md text-headline-md text-on-primary" data-test="net-revenue">
                    {{ $this->earnedTotal->minus($this->commissionTotal)->format() }}
                </span>
                <span class="font-label-sm text-label-sm text-on-primary/75 block">
                    {{ __('Chiffre :amount, dont :commission de commission', [
                        'amount' => $this->earnedTotal->format(),
                        'commission' => $this->commissionTotal->format(),
                    ]) }}
                </span>
            </div>
        </div>

        <div class="relative z-10 flex flex-wrap gap-2">
            <a href="{{ route('farmer.products.create') }}" wire:navigate
               class="h-11 px-4 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-secondary-fixed-dim transition-colors">
                <x-icon name="add_circle" size="18" />
                {{ __('Produit') }}
            </a>

            <a href="{{ route('farmer.trainings.create') }}" wire:navigate
               class="h-11 px-4 rounded-full bg-on-primary/15 font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-on-primary/25 transition-colors">
                <x-icon name="video_call" size="18" />
                {{ __('Formation') }}
            </a>

            <a href="{{ route('farmer.orders') }}" wire:navigate
               class="h-11 px-4 rounded-full bg-on-primary/15 font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-on-primary/25 transition-colors">
                <x-icon name="local_shipping" size="18" />
                {{ __('Commandes') }}
            </a>
        </div>
    </section>

    {{-- Indicateurs --}}
    <section class="grid grid-cols-2 gap-space-xs lg:grid-cols-4 lg:gap-space-sm">
        @foreach ([
            [
                'icon' => 'payments',
                'tint' => 'tertiary',
                'label' => __('Chiffre encaissé'),
                'value' => $this->earnedTotal->format(),
                'detail' => __('dont :amount de commission', ['amount' => $this->commissionTotal->format()]),
            ],
            [
                'icon' => 'inventory',
                'tint' => 'secondary',
                'label' => __('À préparer'),
                'value' => (string) $this->toPrepare,
                'detail' => trans_choice(':count sous-commande payée|:count sous-commandes payées', $this->toPrepare, ['count' => $this->toPrepare]),
            ],
            [
                'icon' => 'storefront',
                'tint' => 'primary',
                'label' => __('Produits publiés'),
                'value' => (string) $this->publishedProducts,
                'detail' => $this->inReview > 0
                    ? trans_choice(':count en modération|:count en modération', $this->inReview, ['count' => $this->inReview])
                    : __('rien en modération'),
            ],
            [
                'icon' => 'school',
                'tint' => 'primary',
                'label' => __('Formations publiées'),
                'value' => (string) $this->publishedTrainings,
                'detail' => trans_choice(':count inscrit|:count inscrits', $this->trainingBuyers, ['count' => $this->trainingBuyers]),
            ],
        ] as $kpi)
            <div class="rounded-xl bg-surface-container-lowest p-space-sm shadow-card flex flex-col gap-1">
                <span @class([
                    'w-9 h-9 rounded-full flex items-center justify-center',
                    'bg-tertiary-fixed text-on-tertiary-fixed-variant' => $kpi['tint'] === 'tertiary',
                    'bg-secondary-fixed text-on-secondary-fixed-variant' => $kpi['tint'] === 'secondary',
                    'bg-primary-fixed text-primary' => $kpi['tint'] === 'primary',
                ])>
                    <x-icon :name="$kpi['icon']" size="18" />
                </span>

                <span class="font-label-sm text-label-sm text-text-secondary mt-1">{{ $kpi['label'] }}</span>
                <span class="font-headline-sm text-headline-sm text-text-primary truncate">{{ $kpi['value'] }}</span>
                <span class="font-label-sm text-label-sm text-text-secondary leading-tight">{{ $kpi['detail'] }}</span>
            </div>
        @endforeach
    </section>

    {{-- Ventes hebdomadaires --}}
    <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card">
        <x-weekly-bars :series="$this->weeklySales"
                       :title="__('Ventes hebdomadaires')"
                       :subtitle="__('4 dernières semaines, en FCFA')" />
    </section>

    {{-- File de travail --}}
    <section class="flex flex-col gap-space-sm">
        <div class="flex items-center justify-between gap-space-sm">
            <h2 class="font-label-lg text-label-lg text-text-secondary uppercase tracking-wide">
                {{ __('À préparer maintenant') }}
            </h2>

            <a href="{{ route('farmer.orders') }}" wire:navigate
               class="font-label-sm text-label-sm text-primary font-semibold hover:underline shrink-0">
                {{ __('Toutes les commandes') }}
            </a>
        </div>

        @if ($this->pendingWork()->isEmpty())
            <div class="flex items-center gap-space-sm rounded-xl bg-[#e8f5e9] px-space-md py-space-sm">
                <x-icon name="check_circle" size="20" class="text-status-success shrink-0" />
                <p class="font-body-md text-body-md text-status-success">
                    {{ __('Rien à préparer pour l\'instant.') }}
                </p>
            </div>
        @else
            <div class="flex flex-col gap-space-sm">
                @foreach ($this->pendingWork() as $subOrder)
                    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 sm:flex-1">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="font-headline-sm text-headline-sm text-text-primary truncate">{{ $subOrder->reference }}</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-secondary shrink-0"></span>
                                <span class="font-label-sm text-label-sm text-text-secondary truncate">{{ $subOrder->order->client->name }}</span>
                            </div>

                            <p class="font-label-sm text-label-sm text-text-secondary truncate mt-0.5">
                                {{ $subOrder->items->map(fn ($item) => $item->product->name)->implode(', ') }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-space-sm sm:shrink-0">
                            <span class="font-price-tag text-price-tag text-primary">{{ $subOrder->subtotal_amount->format() }}</span>

                            <a href="{{ route('farmer.orders') }}" wire:navigate
                               class="h-10 px-4 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                                {{ __('Préparer') }}
                                <x-icon name="arrow_forward" size="16" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
