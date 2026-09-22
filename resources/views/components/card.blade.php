{{--
    Carte de niveau 1 : surface blanche sur le canevas ivoire, contour chaud
    d'un pixel, ombre ambiante douce, rayon 14 px comme les cartes produit des
    maquettes.
--}}
@props(['padded' => true])

<div {{ $attributes->class([
    'bg-surface-container-lowest border border-border-warm rounded-[14px] shadow-card',
    'p-space-md' => $padded,
]) }}>
    {{ $slot }}
</div>
