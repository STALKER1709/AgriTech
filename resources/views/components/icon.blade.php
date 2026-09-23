{{--
    Icône Material Symbols Outlined, la seule famille employée par les
    maquettes. La police est servie depuis node_modules : rien n'est demandé à
    un CDN, l'application reste utilisable hors ligne.

    Usage : <x-icon name="shopping_cart" size="22" />
            <x-icon name="eco" filled size="20" class="text-secondary" />
--}}
@props([
    'name',
    'size' => 24,
    'filled' => false,
    'weight' => null,
])

@php
    $settings = [];

    if ($filled) {
        $settings[] = "'FILL' 1";
    }

    if ($weight !== null) {
        $settings[] = "'wght' {$weight}";
    }
@endphp

<span
    aria-hidden="true"
    {{ $attributes->class(['material-symbols-outlined shrink-0 select-none']) }}
    style="font-size: {{ $size }}px; line-height: 1;@if ($settings) font-variation-settings: {{ implode(', ', $settings) }};@endif"
>{{ $name }}</span>
