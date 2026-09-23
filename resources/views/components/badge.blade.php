{{--
    Pastille d'état (DESIGN.md, « Chips & Filters → Counter / Status Badges ») :
    pilule de 24 px, typographie `label-sm`, teintes sémantiques exactes.

    variant : success | warning | danger | gold | neutral | primary
--}}
@props(['variant' => 'neutral', 'icon' => null])

@php
    $variants = [
        'success' => 'bg-[#e8f5e9] text-status-success',
        'warning' => 'bg-[#fff3e0] text-status-warning',
        'danger' => 'bg-[#ffebee] text-status-error',
        'gold' => 'bg-[#fef9e7] text-[#8d6b00] border border-tertiary-fixed-dim',
        'primary' => 'bg-primary-fixed text-on-primary-fixed',
        'neutral' => 'bg-surface-container text-text-secondary',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex h-6 items-center gap-1 rounded-full px-2.5 font-label-sm text-label-sm',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    @if ($icon)<x-icon :name="$icon" size="14" />@endif
    {{ $slot }}
</span>
