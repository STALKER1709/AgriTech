<div class="flex w-full flex-col pb-32 lg:pb-28">
    {{-- Retour et fil d'Ariane — `agritech_fiche_produit` --}}
    <div class="pt-space-xs pb-2 flex items-center justify-between gap-space-sm">
        <a href="{{ route('catalog.browse') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-text-secondary hover:text-primary transition-colors py-1">
            <x-icon name="arrow_back" size="20" />
            <span class="font-label-sm text-label-sm">{{ __('Retour au catalogue') }}</span>
        </a>

        <div class="flex items-center gap-1 text-text-secondary font-label-sm text-label-sm min-w-0">
            <span class="truncate">{{ $product->category->name }}</span>
            <span>&gt;</span>
            <span class="font-body-md-bold text-body-md-bold text-primary truncate">{{ $product->name }}</span>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-2 lg:gap-space-lg lg:items-start">
        {{-- Galerie --}}
        <div class="mt-1 lg:sticky lg:top-20">
            <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-surface-container shadow-card">
                @if ($this->currentImage())
                    <img src="{{ $this->currentImage()->url() }}" alt="{{ $product->name }}"
                         class="w-full h-full object-cover transition-all duration-300" />
                @else
                    <div class="flex h-full w-full items-center justify-center">
                        <x-icon name="photo_camera" size="40" class="text-surface-container-highest" />
                    </div>
                @endif

                <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-status-success text-on-primary font-label-sm text-label-sm font-semibold shadow-card">
                        <x-icon name="eco" size="14" />
                        {{ $product->category->name }}
                    </span>

                    @if ($product->farmer->farmerProfile?->region)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-bold shadow-card">
                            <x-icon name="location_on" size="14" />
                            {{ __('Terroir :region', ['region' => $product->farmer->farmerProfile->region]) }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-3 gap-2.5 mt-2.5">
                    @foreach ($product->images as $image)
                        <button type="button" wire:click="showImage({{ $image->id }})"
                                aria-label="{{ __('Voir l\'image :number', ['number' => $loop->iteration]) }}"
                                @class([
                                    'aspect-square rounded-xl overflow-hidden bg-surface-container relative transition-all duration-200 cursor-pointer',
                                    'ring-2 ring-primary' => $this->currentImage()?->id === $image->id,
                                ])>
                            <img src="{{ $image->url() }}" alt="" loading="lazy" class="w-full h-full object-cover" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-col">
            {{-- Stock et nom --}}
            <div class="mt-space-md lg:mt-0">
                <div class="flex items-center justify-between gap-2">
                    @if ($product->isInStock())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-status-success/10 text-status-success font-label-sm text-label-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-status-success"></span>
                            {{ __(':quantity :unit disponibles en stock', [
                                'quantity' => $product->stock_quantity->format(),
                                'unit' => $product->unit->countLabel($product->stock_quantity),
                            ]) }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-status-error/10 text-status-error font-label-sm text-label-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-status-error"></span>
                            {{ __('Rupture de stock') }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-2 font-headline-lg-mobile text-headline-lg-mobile lg:font-headline-lg lg:text-headline-lg text-text-primary tracking-tight">
                    {{ $product->name }}
                </h1>

                <div class="mt-2.5 p-3 rounded-2xl bg-surface-container-low flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-1.5 min-w-0">
                        <span class="font-display-lg-mobile text-display-lg-mobile text-primary font-bold">
                            {{ $product->unit_price->format() }}
                        </span>
                        <span class="font-body-md text-body-md text-text-secondary truncate">
                            / {{ $product->unit->label() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Producteur --}}
            <div class="mt-space-md">
                @php($profile = $product->farmer->farmerProfile)
                <div class="p-3.5 rounded-2xl bg-surface-container shadow-card flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-headline-sm text-[15px]">
                                {{ $product->farmer->initials() }}
                            </div>
                            <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-status-success text-on-primary flex items-center justify-center">
                                <x-icon name="check" size="12" />
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h2 class="font-headline-sm text-headline-sm text-text-primary truncate">
                                    {{ $profile?->farm_name ?? $product->farmer->name }}
                                </h2>
                                <x-icon name="verified" size="16" class="text-primary" />
                            </div>

                            <p class="font-label-sm text-label-sm text-text-secondary truncate">
                                {{ $product->farmer->name }}
                            </p>

                            @if ($profile)
                                <p class="font-label-sm text-label-sm text-text-secondary flex items-center gap-0.5 mt-0.5">
                                    <x-icon name="place" size="13" />
                                    {{ collect([$profile->city, $profile->region])->filter()->join(', ') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm">
                            <x-icon name="shield" size="13" />
                            {{ __('Compte validé par AgriTech') }}
                        </span>

                        @if ($product->farmer->created_at)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-high text-text-secondary font-label-sm text-label-sm">
                                <x-icon name="calendar_today" size="13" />
                                {{ __('Membre depuis :year', ['year' => $product->farmer->created_at->year]) }}
                            </span>
                        @endif
                    </div>

                    <button type="button" wire:click="contactFarmer" data-test="contact-farmer"
                            class="w-full h-11 rounded-full bg-surface-white text-secondary hover:bg-secondary-fixed/40 transition-colors font-label-lg text-label-lg font-semibold flex items-center justify-center gap-2 shadow-card cursor-pointer">
                        <x-icon name="chat" size="18" />
                        {{ __('Contacter le producteur') }}
                    </button>
                </div>
            </div>

            {{-- Quantité --}}
            @if ($product->isInStock())
                <div class="mt-space-md">
                    <div class="p-4 rounded-2xl bg-surface-container-lowest shadow-card">
                        <div class="flex items-center justify-between gap-space-sm">
                            <div class="min-w-0">
                                <span class="font-label-lg text-label-lg text-text-primary block">{{ __('Quantité souhaitée') }}</span>
                                <span class="font-label-sm text-label-sm text-text-secondary">{{ $product->unit->label() }}</span>
                            </div>

                            <div class="flex items-center bg-surface-container rounded-full p-1 gap-2 shrink-0">
                                <button type="button" wire:click="decrement" aria-label="{{ __('Diminuer la quantité') }}"
                                        data-test="quantity-minus"
                                        class="w-9 h-9 rounded-full bg-surface-white text-text-primary flex items-center justify-center shadow-card active:scale-90 transition-transform cursor-pointer">
                                    <x-icon name="remove" size="18" />
                                </button>

                                <input type="text" wire:model.live.debounce.400ms="quantity" data-test="quantity"
                                       aria-label="{{ __('Quantité') }}"
                                       class="w-10 bg-transparent text-center font-body-md-bold text-body-md-bold text-text-primary focus:outline-none" />

                                <button type="button" wire:click="increment" aria-label="{{ __('Augmenter la quantité') }}"
                                        data-test="quantity-plus"
                                        class="w-9 h-9 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-card active:scale-90 transition-transform cursor-pointer">
                                    <x-icon name="add" size="18" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Accordéons. La maquette en a trois ; le troisième promet des
                 modes de livraison que la plateforme n'offre pas. --}}
            <div class="mt-space-md flex flex-col gap-2.5">
                <details class="group rounded-2xl bg-surface-container-lowest p-4 shadow-card" open>
                    <summary class="flex items-center justify-between cursor-pointer list-none font-headline-sm text-headline-sm text-text-primary select-none">
                        <span class="flex items-center gap-2">
                            <x-icon name="nutrition" size="20" class="text-primary" />
                            {{ __('Description du produit') }}
                        </span>
                        <x-icon name="expand_more" size="20" class="text-text-secondary transition-transform duration-200 group-open:rotate-180" />
                    </summary>

                    <div class="mt-3 text-text-secondary font-body-md text-body-md leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </div>
                </details>

                @if ($profile)
                    <details class="group rounded-2xl bg-surface-container-lowest p-4 shadow-card">
                        <summary class="flex items-center justify-between cursor-pointer list-none font-headline-sm text-headline-sm text-text-primary select-none">
                            <span class="flex items-center gap-2">
                                <x-icon name="terrain" size="20" class="text-primary" />
                                {{ __('Origine et exploitation') }}
                            </span>
                            <x-icon name="expand_more" size="20" class="text-text-secondary transition-transform duration-200 group-open:rotate-180" />
                        </summary>

                        <div class="mt-3 text-text-secondary font-body-md text-body-md leading-relaxed flex flex-col gap-2">
                            <p>{{ $profile->description }}</p>

                            <div class="p-2.5 rounded-xl bg-surface-container-high text-text-primary font-label-sm text-label-sm flex items-center gap-1.5">
                                <x-icon name="place" size="16" class="text-primary" />
                                {{ collect([$profile->city, $profile->region])->filter()->join(', ') }}
                            </div>
                        </div>
                    </details>
                @endif
            </div>

            {{-- Du même producteur --}}
            @php($others = $this->alsoFromFarmer())
            @if ($others->isNotEmpty())
                <div class="mt-space-md flex flex-col gap-space-sm">
                    <h2 class="font-headline-md text-headline-md text-text-primary tracking-tight">
                        {{ __('Du même producteur') }}
                    </h2>

                    <div class="grid grid-cols-2 gap-space-sm">
                        @foreach ($others as $other)
                            <x-product-card :product="$other" wire:key="other-{{ $other->id }}" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Barre d'achat fixe. Au-dessus de la barre d'onglets sur mobile, collée
         en bas sur écran large où il n'y a pas d'onglets. --}}
    <div class="fixed bottom-16 lg:bottom-0 inset-x-0 z-40 bg-surface-white/95 backdrop-blur-xl shadow-float px-gutter lg:px-space-lg py-2.5 flex items-center gap-3"
         style="padding-bottom: calc(0.625rem + env(safe-area-inset-bottom, 0px));">
        <div class="flex flex-col min-w-[90px]">
            <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Total estimé') }}</span>
            <span class="font-price-tag text-price-tag text-text-primary leading-tight" data-test="estimated-total">
                {{ $this->estimatedTotal()->format() }}
            </span>
            <span class="font-label-sm text-[10px] text-status-success font-medium">{{ __('Prix du producteur') }}</span>
        </div>

        @if (! $product->isInStock())
            <div class="flex-1 h-12 rounded-full bg-surface-container text-text-secondary font-label-lg text-label-lg font-bold flex items-center justify-center">
                {{ __('Momentanément épuisé') }}
            </div>
        @elseif ($this->isVisitor() || $this->canAddToCart())
            <button type="button" wire:click="addToCart" wire:loading.attr="disabled" data-test="add-to-cart"
                    class="flex-1 h-12 rounded-full bg-primary text-on-primary font-label-lg text-label-lg font-bold flex items-center justify-center gap-2 shadow-raised active:scale-[0.98] transition-all hover:bg-primary-container cursor-pointer">
                <x-icon name="shopping_cart" size="20" />
                <span>{{ $this->isVisitor() ? __('Se connecter pour commander') : __('Ajouter au panier') }}</span>
            </button>
        @else
            <div class="flex-1 h-12 rounded-full bg-surface-container text-text-secondary font-label-lg text-label-lg flex items-center justify-center text-center px-2">
                {{ __('Réservé aux comptes clients') }}
            </div>
        @endif

        @if ($this->canAddToCart())
            <button type="button" wire:click="contactFarmer" aria-label="{{ __('Négocier par message') }}"
                    title="{{ __('Négocier par message') }}" data-test="negotiate"
                    class="w-12 h-12 rounded-full bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center shadow-card active:scale-95 transition-transform shrink-0 cursor-pointer">
                <x-icon name="forum" size="22" />
            </button>
        @endif
    </div>
</div>
