{{--
    Carte produit, marquage repris tel quel de `agritech_catalogue_produits` :
    surface blanche à rayon 12 px, padding 10 px, image carrée à rayon 8 px
    avec une pastille en surimpression, ligne de localisation en terre cuite,
    titre `headline-sm` sur deux lignes, nom de l'exploitation, prix
    `price-tag` vert et bouton rond « + » de 40 px.

    La pastille de la maquette annonce « Frais du jour » ou « Bio ». Aucune
    donnée ne porte cela : la place est tenue par la catégorie, que le serveur
    connaît vraiment. Mieux vaut une pastille exacte qu'une promesse inventée.

    `action` : nom d'une méthode Livewire du composant parent, appelée avec
    l'identifiant du produit. Sans elle, le « + » mène à la fiche produit.
--}}
@props(['product', 'action' => null])

@php
    $profile = $product->farmer->farmerProfile;
    $place = collect([$profile?->city, $profile?->region])->filter()->join(', ');
@endphp

<article {{ $attributes->class('bg-surface-container-lowest rounded-xl p-2.5 flex flex-col justify-between shadow-card hover:shadow-raised transition-shadow relative') }}>
    <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate class="flex flex-col gap-2">
        <div class="relative w-full aspect-square rounded-lg overflow-hidden bg-surface-container">
            @if ($product->images->isNotEmpty())
                <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" loading="lazy"
                     class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-300" />
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <x-icon name="photo_camera" size="28" class="text-surface-container-highest" />
                </div>
            @endif

            <span class="absolute top-2 left-2 bg-status-success text-on-primary font-label-sm text-label-sm text-[10px] font-bold px-2 py-0.5 rounded-full shadow-card">
                {{ $product->category->name }}
            </span>
        </div>

        <div class="flex flex-col min-w-0">
            <div class="flex items-center gap-1 text-secondary">
                <x-icon name="location_on" size="14" />
                <span class="font-label-sm text-label-sm text-text-secondary truncate">{{ $place }}</span>
            </div>

            <h2 class="font-headline-sm text-headline-sm text-text-primary text-[15px] leading-tight line-clamp-2 mt-0.5">
                {{ $product->name }}
            </h2>

            <span class="font-label-sm text-label-sm text-text-secondary/90 truncate">
                {{ $profile?->farm_name ?? $product->farmer->name }}
            </span>
        </div>
    </a>

    <div class="flex items-end justify-between pt-2.5 mt-1">
        <div class="flex flex-col min-w-0">
            <span class="font-price-tag text-price-tag text-primary leading-tight text-[16px]">
                {{ $product->unit_price->format() }}
            </span>
            <span class="font-label-sm text-label-sm text-text-secondary text-[11px]">
                / {{ $product->unit->shortLabel() }}
            </span>
        </div>

        @if ($action)
            <button type="button" wire:click="{{ $action }}({{ $product->id }})"
                    wire:loading.attr="disabled" wire:target="{{ $action }}({{ $product->id }})"
                    aria-label="{{ __('Ajouter au panier') }}"
                    data-test="add-{{ $product->id }}"
                    class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center hover:bg-primary/90 active:scale-90 transition-transform shadow-card shrink-0 cursor-pointer">
                <x-icon name="add" size="20" />
            </button>
        @else
            <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate
               aria-label="{{ __('Voir le produit') }}"
               class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center hover:bg-primary/90 active:scale-90 transition-transform shadow-card shrink-0">
                <x-icon name="add" size="20" />
            </a>
        @endif
    </div>
</article>
