{{--
    Une ligne de la liste de lecture du lecteur de formation. La maquette
    marque les modules « validés » d'une coche verte ; aucune table ne
    retient où un client s'est arrêté, donc la seule distinction affichée est
    celle que le serveur connaît : le module ouvert, et les autres.
--}}
@props(['module', 'number', 'current' => false])

<button type="button" wire:click="open({{ $module->id }})"
        @class([
            'w-full text-left rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-card transition-colors',
            'bg-primary-fixed' => $current,
            'bg-surface-container-lowest hover:bg-surface-container' => ! $current,
        ])
        data-test="module">
    <span class="flex items-center gap-2.5 min-w-0">
        <span @class([
            'w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-label-sm text-label-sm font-bold',
            'bg-primary text-on-primary' => $current,
            'bg-surface-container-high text-text-secondary' => ! $current,
        ])>
            @if ($current)
                <x-icon name="play_arrow" size="18" filled />
            @else
                {{ $number }}
            @endif
        </span>

        <span class="flex flex-col min-w-0">
            <span @class([
                'font-label-lg text-label-lg truncate',
                'text-on-primary-fixed' => $current,
                'text-text-primary' => ! $current,
            ])>{{ $module->title }}</span>
            <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                <x-icon :name="$module->type === \App\Enums\TrainingContentType::Video ? 'play_circle' : 'description'" size="14" />
                {{ $module->type->label() }}
            </span>
        </span>
    </span>

    <x-icon :name="$current ? 'graphic_eq' : 'chevron_right'" size="18" class="shrink-0 text-text-secondary" />
</button>
