{{--
    Puce de filtre (DESIGN.md, « Category Chips ») : pilule de 36 à 40 px,
    blanche et bordée au repos, verte et blanche à l'état sélectionné.
--}}
@props(['selected' => false, 'href' => null, 'icon' => null])

@php
    $classes = 'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full px-4 font-label-lg text-label-lg transition-colors cursor-pointer '
        .($selected
            ? 'bg-primary text-on-primary font-semibold'
            : 'bg-surface-container-lowest border border-border-warm text-text-secondary hover:text-text-primary');
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" size="18" />@endif
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" size="18" />@endif
        {{ $slot }}
    </button>
@endif
