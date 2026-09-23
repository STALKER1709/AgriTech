{{--
    Le graphique de `agritech_admin_tableau_de_bord` : le volume mensuel,
    réparti entre deux natures de recette.

    Deux séries d'une même unité, donc **un seul axe** et des barres
    empilées — la hauteur dit le total du mois, la coupure dit d'où il vient.
    Un second axe pour comparer deux montants serait la faute classique.

    Les deux teintes viennent du design system, et leur écart a été vérifié
    au validateur pour les daltonismes. L'or contraste peu avec la surface :
    la légende porte donc les totaux en chiffres, et chaque segment sa valeur
    au survol — la couleur n'est jamais seule à porter l'information.

    `series` : [['label' => 'janv.', 'orders' => Money, 'trainings' => Money,
                 'total' => Money, 'share' => 0.0–1.0]]
--}}
@props(['series', 'title' => null, 'subtitle' => null, 'legend'])

@php
    $totalOrders = collect($series)->reduce(
        fn ($carry, $point) => $carry->plus($point['orders']),
        \App\Support\Money::fromInteger(0),
    );
    $totalTrainings = collect($series)->reduce(
        fn ($carry, $point) => $carry->plus($point['trainings']),
        \App\Support\Money::fromInteger(0),
    );
@endphp

<figure {{ $attributes->class('flex flex-col gap-space-sm') }}>
    <figcaption class="flex flex-wrap items-baseline justify-between gap-space-sm">
        <span class="font-headline-sm text-headline-sm text-text-primary">{{ $title }}</span>
        @if ($subtitle)
            <span class="font-label-sm text-label-sm text-text-secondary">{{ $subtitle }}</span>
        @endif
    </figcaption>

    {{-- Légende chiffrée : identité et valeur, jamais la couleur seule. --}}
    <div class="flex flex-wrap items-center gap-x-space-md gap-y-1">
        @foreach ([
            ['bg-primary', $legend['orders'], $totalOrders],
            ['bg-tertiary-fixed-dim', $legend['trainings'], $totalTrainings],
        ] as [$swatch, $name, $amount])
            <span class="flex items-center gap-1.5 font-label-sm text-label-sm text-text-secondary">
                <span class="w-3 h-3 rounded-sm {{ $swatch }} shrink-0"></span>
                {{ $name }}
                <span class="text-text-primary font-semibold">{{ $amount->format() }}</span>
            </span>
        @endforeach
    </div>

    <div class="flex items-end gap-space-xs h-44" role="list">
        @foreach ($series as $point)
            @php($height = max($point['share'] * 100, 1.5))
            @php($ordersShare = $point['total']->amount > 0 ? $point['orders']->amount / $point['total']->amount : 0)

            <div class="flex-1 flex flex-col items-center justify-end gap-1.5 h-full min-w-0" role="listitem">
                <span class="font-label-sm text-label-sm text-text-primary font-semibold whitespace-nowrap">
                    {{ $point['total']->formatCompact() }}
                </span>

                {{-- La pile : formations dessus, produits dessous, séparées
                     par deux pixels de surface pour que la coupure se lise. --}}
                <div class="w-full flex justify-center" style="height: {{ $height }}%;">
                    <span class="w-[48%] min-w-[12px] h-full flex flex-col justify-end rounded-t overflow-hidden">
                        @if ($point['trainings']->amount > 0)
                            <span class="w-full bg-tertiary-fixed-dim rounded-t"
                                  style="height: {{ (1 - $ordersShare) * 100 }}%;"
                                  title="{{ $legend['trainings'] }} — {{ $point['label'] }} : {{ $point['trainings']->format() }}"></span>
                            <span class="w-full h-[2px] bg-surface-container-lowest shrink-0" aria-hidden="true"></span>
                        @endif

                        <span @class(['w-full bg-primary', 'rounded-t' => $point['trainings']->amount === 0])
                              style="height: {{ $ordersShare * 100 }}%;"
                              title="{{ $legend['orders'] }} — {{ $point['label'] }} : {{ $point['orders']->format() }}"></span>
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex items-start gap-space-xs border-t border-surface-container-high pt-1.5">
        @foreach ($series as $point)
            <span class="flex-1 text-center font-label-sm text-label-sm text-text-secondary truncate">{{ $point['label'] }}</span>
        @endforeach
    </div>
</figure>
