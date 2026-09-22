{{--
    Champ de formulaire du design system : libellé `label-lg` gris chaud
    au-dessus, contrôle de 50 px à rayon 12 px, message d'erreur dessous.

    Le contrôle lui-même est passé en contenu et porte la classe `ds-input`.
--}}
@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="font-label-lg text-label-lg text-text-secondary">
            {{ $label }}
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="font-label-sm text-label-sm text-text-secondary">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
            <x-icon name="error" size="14" />
            {{ $error }}
        </p>
    @endif
</div>
