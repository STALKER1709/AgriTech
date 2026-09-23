{{--
    Bouton du design system (DESIGN.md, « Components → Buttons ») :
    pilule, hauteur minimale 48 px, 24 px de marge horizontale, graisse 600.

    variant : primary | secondary | outline | ghost | danger
    size    : md (48 px) | sm (40 px)

    Rend un <a> si `href` est fourni, un <button> sinon.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconTrailing' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-label-lg text-label-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

    $sizes = [
        'md' => 'min-h-12 px-6',
        'sm' => 'min-h-10 px-4',
    ];

    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary/90 active:bg-[#003d1d]',
        'secondary' => 'bg-secondary text-on-secondary hover:bg-secondary/90',
        'outline' => 'border-[1.5px] border-primary text-primary hover:bg-primary/5',
        'ghost' => 'text-text-primary hover:bg-surface-container',
        'danger' => 'bg-error text-on-error hover:bg-error/90',
    ];

    $classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']));
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" size="20" />@endif
        {{ $slot }}
        @if ($iconTrailing)<x-icon :name="$iconTrailing" size="20" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-icon :name="$icon" size="20" />@endif
        {{ $slot }}
        @if ($iconTrailing)<x-icon :name="$iconTrailing" size="20" />@endif
    </button>
@endif
