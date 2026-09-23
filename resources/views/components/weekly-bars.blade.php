{{--
    Le graphique de `agritech_tableau_de_bord_vendeur` : quatre barres, une
    par semaine, en HTML et en proportions — pas de bibliothèque, pas de
    SVG à dimension fixe, donc rien à recalculer quand la colonne change de
    largeur.

    Une seule série, donc une seule teinte et pas de légende — le titre
    nomme la mesure. Les valeurs sont écrites au-dessus des barres plutôt
    que déduites d'un axe : avec quatre points, l'axe coûterait plus de
    place qu'il n'en fait gagner. Les chiffres portent l'encre du texte, pas
    la couleur de la série, et chaque barre expose sa valeur au survol comme
    aux lecteurs d'écran.

    `series` : [['label' => 'S1', 'amount' => Money, 'share' => 0.0–1.0]]
--}}
@props(['series', 'title' => null, 'subtitle' => null])

@php
    // Géométrie en pourcentage de la hauteur utile : le SVG s'étire, les
    // proportions non.
    $count = max(count($series), 1);
    $slot = 100 / $count;
    $barWidth = $slot * 0.42;
@endphp

<figure {{ $attributes->class('flex flex-col gap-space-sm') }}>
    @if ($title)
        <figcaption class="flex items-baseline justify-between gap-space-sm">
            <span class="font-headline-sm text-headline-sm text-text-primary">{{ $title }}</span>
            @if ($subtitle)
                <span class="font-label-sm text-label-sm text-text-secondary shrink-0">{{ $subtitle }}</span>
            @endif
        </figcaption>
    @endif

    <div class="flex items-end gap-space-xs h-40" role="list">
        @foreach ($series as $point)
            <div class="flex-1 flex flex-col items-center justify-end gap-1.5 h-full min-w-0" role="listitem">
                {{-- Écriture courte : quatre montants complets ne tiennent
                     pas côte à côte à 360 px. Le montant exact reste dans
                     l'infobulle de la barre. --}}
                <span class="font-label-sm text-label-sm text-text-primary font-semibold whitespace-nowrap">
                    {{ $point['amount']->formatCompact() }}
                </span>

                {{-- Hauteur minimale de 2 px : une semaine sans vente reste
                     visible comme une semaine, pas comme une absence. --}}
                <div class="w-full flex justify-center" style="height: {{ max($point['share'] * 100, 1.5) }}%;">
                    <span class="w-[42%] min-w-[10px] h-full rounded-t bg-primary"
                          title="{{ $point['label'] }} — {{ $point['amount']->format() }}"></span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex items-start gap-space-xs border-t border-surface-container-high pt-1.5">
        @foreach ($series as $point)
            <span class="flex-1 text-center font-label-sm text-label-sm text-text-secondary truncate">
                {{ $point['label'] }}
            </span>
        @endforeach
    </div>
</figure>
