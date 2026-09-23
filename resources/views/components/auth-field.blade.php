{{--
    Champ des écrans d'authentification, repris de `agritech_connexion` et
    `agritech_inscription_agriculteur` : libellé au-dessus, contrôle de 50 px
    à rayon 12 px précédé d'une icône, message d'erreur dessous.

    `wire` pilote un composant Livewire ; `name` un formulaire POST classique.
    Les deux ne se mélangent pas : l'un ou l'autre.

    type : text | email | tel | password | select | textarea
    options : pour un `select`, la liste des valeurs
--}}
@props([
    'type' => 'text',
    'icon' => null,
    'label' => null,
    'name' => null,
    'wire' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'autocomplete' => null,
    'options' => [],
    'hint' => null,
    'rows' => 3,
    'prefix' => null,
])

@php
    $id = $name ?? $wire ?? 'field-'.uniqid();
    $error = $name ? $errors->first($name) : ($wire ? $errors->first($wire) : null);
    $binding = new \Illuminate\View\ComponentAttributeBag($wire ? ['wire:model' => $wire] : ['name' => $name]);
    $control = 'w-full bg-transparent font-body-md text-body-md text-on-surface placeholder:text-outline outline-none';
@endphp

<div {{ $attributes->class('flex flex-col gap-1.5') }} x-data="{ show: false }">
    @if ($label)
        <label for="{{ $id }}" class="font-label-lg text-label-lg text-text-secondary">
            {{ $label }}
            @if (! $required)
                <span class="text-outline">{{ __('(facultatif)') }}</span>
            @endif
        </label>
    @endif

    <div @class([
        'flex bg-surface-container-low rounded-xl px-space-sm gap-2 focus-within:bg-surface-white focus-within:shadow-raised transition-all',
        'items-start py-2.5' => $type === 'textarea',
        'items-center py-1.5' => $type !== 'textarea',
        'ring-2 ring-status-error' => $error,
    ])>
        @if ($prefix)
            <span class="font-headline-sm text-headline-sm tracking-tight pl-1 text-on-surface select-none">{{ $prefix }}</span>
            <span class="h-6 w-0.5 bg-outline-variant"></span>
        @elseif ($icon)
            <x-icon :name="$icon" size="20" @class(['text-text-secondary shrink-0', 'mt-1.5' => $type === 'textarea']) />
        @endif

        @if ($type === 'select')
            <select id="{{ $id }}" @required($required) {{ $binding }}
                    class="{{ $control }} h-9 appearance-none cursor-pointer">
                <option value="">{{ $placeholder ?? __('Choisir…') }}</option>
                @foreach ($options as $option)
                    <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <x-icon name="expand_more" size="20" class="text-text-secondary shrink-0 pointer-events-none" />
        @elseif ($type === 'textarea')
            <textarea id="{{ $id }}" rows="{{ $rows }}" @required($required) {{ $binding }}
                      placeholder="{{ $placeholder }}"
                      class="{{ $control }} resize-y leading-relaxed">{{ $wire ? '' : $value }}</textarea>
        @elseif ($type === 'password')
            <input id="{{ $id }}" type="password" :type="show ? 'text' : 'password'"
                   @required($required) {{ $binding }}
                   autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder ?? '••••••••' }}"
                   class="{{ $control }} h-9" />
            <button type="button" x-on:click="show = ! show"
                    :aria-label="show ? '{{ __('Masquer le mot de passe') }}' : '{{ __('Afficher le mot de passe') }}'"
                    class="shrink-0 text-text-secondary hover:text-text-primary transition-colors">
                <x-icon name="visibility" size="20" x-show="! show" />
                <x-icon name="visibility_off" size="20" x-show="show" x-cloak />
            </button>
        @else
            <input id="{{ $id }}" type="{{ $type }}" @required($required) {{ $binding }}
                   value="{{ $wire ? '' : $value }}"
                   autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
                   class="{{ $control }} h-9" />
        @endif
    </div>

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
