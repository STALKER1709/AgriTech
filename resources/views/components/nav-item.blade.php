{{--
    Entrée de navigation latérale, reprise du marquage de
    `agritech_admin_tableau_de_bord` : pilule arrondie 8 px, icône 20 px,
    libellé `label-lg`, pastille de compteur à droite. L'état actif porte le
    fond `primary-container` et le texte `on-primary`.
--}}
@props([
    'href',
    'icon',
    'current' => false,
    'badge' => null,
    'badgeVariant' => 'secondary',
])

<a href="{{ $href }}" wire:navigate
   @class([
       'flex items-center justify-between px-space-sm py-2.5 rounded-lg transition-all',
       'bg-primary-container text-on-primary font-body-md-bold shadow-[0_1px_4px_rgba(0,0,0,0.06)]' => $current,
       'text-text-secondary hover:bg-surface-container hover:text-text-primary' => ! $current,
   ])
   @if ($current) aria-current="page" @endif>
    <div class="flex items-center gap-3">
        <x-icon :name="$icon" size="20" :filled="$current" />
        <span class="font-label-lg text-label-lg">{{ $slot }}</span>
    </div>

    @if ($badge)
        <span @class([
            'inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 rounded-full font-label-sm text-label-sm font-bold',
            'bg-secondary text-on-secondary' => $badgeVariant === 'secondary',
            'bg-error text-on-error' => $badgeVariant === 'error',
        ])>{{ $badge }}</span>
    @endif
</a>
