<div class="flex w-full flex-col gap-space-sm py-space-xs" x-data="{ filters: false }">
    {{-- En-tête contextuel — `agritech_catalogue_produits` --}}
    <section class="flex flex-col gap-1">
        <div class="flex items-center justify-between gap-space-sm">
            <span class="font-label-sm text-label-sm text-secondary uppercase tracking-wider font-semibold">
                {{ __('Cameroun • Terroirs Unis') }}
            </span>
            <span class="inline-flex items-center gap-1 font-label-sm text-label-sm text-status-success bg-status-success/10 px-2 py-0.5 rounded-full shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-status-success"></span>
                {{ __('Réseau en direct') }}
            </span>
        </div>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile lg:font-headline-lg lg:text-headline-lg text-text-primary tracking-tight">
            {{ __('Catalogue des récoltes') }}
        </h1>

        <p class="font-body-md text-body-md text-text-secondary leading-snug">
            {{ __('Des produits frais en direct des producteurs locaux') }}
        </p>
    </section>

    {{-- Recherche et contrôles --}}
    <section class="flex flex-col gap-space-sm">
        <div class="relative w-full flex items-center">
            <x-icon name="search" size="22" class="absolute left-3.5 text-text-secondary pointer-events-none" />

            <input type="search" wire:model.live.debounce.300ms="search" data-test="search-input"
                   placeholder="{{ __('Rechercher par légume, fruit, ville…') }}"
                   class="w-full h-12 pl-11 pr-10 rounded-xl bg-surface-container-lowest text-text-primary placeholder:text-text-secondary/70 font-body-md text-body-md focus:outline-none focus:bg-surface-white shadow-card transition-all" />

            @if ($search !== '')
                <button type="button" wire:click="$set('search', '')" aria-label="{{ __('Effacer la recherche') }}"
                        class="absolute right-3 text-text-secondary/60 hover:text-text-primary cursor-pointer">
                    <x-icon name="close" size="18" />
                </button>
            @endif
        </div>

        <div class="flex items-center justify-between gap-space-sm">
            <button type="button" x-on:click="filters = true" data-test="open-filters"
                    class="flex-1 h-11 px-3 rounded-xl bg-surface-container-low hover:bg-surface-container flex items-center justify-between transition-colors shadow-card cursor-pointer">
                <span class="flex items-center gap-2">
                    <x-icon name="tune" size="20" class="text-primary" />
                    <span class="font-label-lg text-label-lg text-text-primary">{{ __('Filtrer') }}</span>
                </span>

                @if ($this->activeFilterCount() > 0)
                    <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm text-[11px] font-bold flex items-center justify-center">
                        {{ $this->activeFilterCount() }}
                    </span>
                @endif
            </button>

            <div class="relative flex-1">
                <div class="w-full h-11 px-3 rounded-xl bg-surface-container-low flex items-center justify-between shadow-card">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <x-icon name="sort" size="18" class="text-text-secondary" />
                        <span class="font-label-lg text-label-lg text-text-primary truncate">
                            {{ $this->sorts()[$sort] ?? __('Trier') }}
                        </span>
                    </span>
                    <x-icon name="expand_more" size="18" class="text-text-secondary" />
                </div>

                {{-- Le <select> natif couvre le bouton : même dessin, et le
                     sélecteur du système sur mobile, sans une ligne de JS. --}}
                <select wire:model.live="sort" data-test="sort"
                        aria-label="{{ __('Trier les produits') }}"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    @foreach ($this->sorts() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-1 gap-space-sm">
            <span class="font-label-sm text-label-sm font-semibold text-text-primary">
                {{ trans_choice('{0}Aucun produit disponible|{1}:count produit disponible au Cameroun|[2,*]:count produits disponibles au Cameroun', $this->total()) }}
            </span>

            <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1 shrink-0">
                <x-icon name="eco" size="15" class="text-status-success" />
                {{ __('Circuits courts') }}
            </span>
        </div>
    </section>

    {{-- Puces de région et de catégorie, défilement horizontal --}}
    <section class="-mx-gutter lg:-mx-space-lg">
        <div class="flex items-center gap-2 overflow-x-auto px-gutter lg:px-space-lg no-scrollbar py-0.5">
            <button type="button" wire:click="resetFilters" data-test="filter-all"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-lg text-label-lg shadow-card flex items-center gap-1.5 transition-transform active:scale-95 cursor-pointer',
                        'bg-primary text-on-primary font-semibold' => $this->activeFilterCount() === 0,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $this->activeFilterCount() > 0,
                    ])>
                <span>{{ __('Tous') }}</span>
                @if ($this->activeFilterCount() === 0)
                    <span class="w-2 h-2 rounded-full bg-primary-fixed"></span>
                @endif
            </button>

            @foreach ($this->regions() as $regionOption)
                <button type="button" wire:click="$set('region', @js($region === $regionOption ? '' : $regionOption))"
                        @class([
                            'shrink-0 h-9 px-3.5 rounded-full font-label-lg text-label-lg shadow-card flex items-center gap-1 transition-colors cursor-pointer',
                            'bg-primary text-on-primary font-semibold' => $region === $regionOption,
                            'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $region !== $regionOption,
                        ])>
                    <x-icon name="location_on" size="16" @class(['text-secondary' => $region !== $regionOption]) />
                    <span>{{ $regionOption }}</span>
                </button>
            @endforeach

            @foreach ($this->categories() as $categoryOption)
                <button type="button" wire:click="$set('category', '{{ $category === $categoryOption->slug ? '' : $categoryOption->slug }}')"
                        @class([
                            'shrink-0 h-9 px-3.5 rounded-full font-label-lg text-label-lg shadow-card flex items-center gap-1 transition-colors cursor-pointer',
                            'bg-primary text-on-primary font-semibold' => $category === $categoryOption->slug,
                            'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $category !== $categoryOption->slug,
                        ])>
                    <span>{{ $categoryOption->name }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Grille --}}
    <section class="py-space-xs">
        @if ($products->isEmpty())
            <div class="p-space-md rounded-xl bg-surface-container flex items-start gap-3 shadow-card">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <x-icon name="search_off" size="22" />
                </div>
                <div class="flex flex-col gap-0.5">
                    <h2 class="font-body-md-bold text-body-md-bold text-text-primary">
                        {{ __('Aucun produit ne correspond') }}
                    </h2>
                    <p class="font-body-md text-body-md text-text-secondary leading-relaxed">
                        {{ __('Modifiez votre recherche, ou réinitialisez les filtres pour revoir toutes les récoltes.') }}
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 gap-space-sm lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" action="addToCart" wire:key="product-{{ $product->id }}" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Charger plus, puis la carte d'information de bas de page --}}
    <section class="pt-space-sm pb-space-md flex flex-col gap-space-md">
        @if ($this->remaining() > 0)
            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" data-test="load-more"
                    class="w-full h-12 rounded-full bg-surface-container-lowest hover:bg-surface-container text-primary font-body-md-bold text-body-md-bold flex items-center justify-center gap-2 shadow-card transition-transform active:scale-[0.98] cursor-pointer">
                <x-icon name="sync" size="20" />
                <span>{{ __('Charger plus de récoltes (:count restantes)', ['count' => $this->remaining()]) }}</span>
            </button>
        @endif

        {{--
            La maquette place ici une promesse de livraison par agences
            partenaires et transport frigorifique. Rien de tel n'existe côté
            serveur : la carte garde sa place et sa forme, son texte dit ce que
            la plateforme fait réellement.
        --}}
        <div class="p-space-md rounded-xl bg-surface-container flex items-start gap-3 shadow-card">
            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <x-icon name="handshake" size="22" />
            </div>
            <div class="flex flex-col gap-0.5">
                <h2 class="font-body-md-bold text-body-md-bold text-text-primary">
                    {{ __('Achat en direct du producteur') }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary text-xs leading-relaxed">
                    {{ __('Vous payez par Mobile Money ; l\'agriculteur prépare votre commande et convient du retrait avec vous par la messagerie.') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Feuille de filtres, glissée du bas — `agritech_catalogue_produits` --}}
    <div x-show="filters" x-cloak class="fixed inset-0 z-[70] flex flex-col justify-end">
        <div x-show="filters" x-transition.opacity class="absolute inset-0 bg-inverse-surface/40 backdrop-blur-[2px]"
             x-on:click="filters = false"></div>

        <div x-show="filters"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="relative w-full bg-surface rounded-t-2xl max-h-[85vh] flex flex-col shadow-float overflow-hidden lg:mx-auto lg:max-w-lg lg:rounded-2xl lg:mb-space-lg">
            <div class="p-4 pb-2 flex flex-col items-center gap-2 shrink-0 bg-surface">
                <div class="w-10 h-1.5 rounded-full bg-border-warm"></div>

                <div class="w-full flex items-center justify-between mt-1">
                    <div class="flex items-center gap-2">
                        <span class="font-headline-sm text-headline-sm text-text-primary">{{ __('Filtres de récolte') }}</span>
                        @if ($this->activeFilterCount() > 0)
                            <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm font-bold">
                                {{ trans_choice('{1}:count actif|[2,*]:count actifs', $this->activeFilterCount()) }}
                            </span>
                        @endif
                    </div>

                    <button type="button" x-on:click="filters = false" aria-label="{{ __('Fermer') }}"
                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-text-secondary cursor-pointer">
                        <x-icon name="close" size="18" />
                    </button>
                </div>
            </div>

            <div class="p-gutter overflow-y-auto flex flex-col gap-5">
                <div class="flex flex-col gap-2.5">
                    <span class="font-label-lg text-label-lg text-text-primary">{{ __('Régions de provenance') }}</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->regions() as $regionOption)
                            <button type="button" wire:click="$set('region', @js($region === $regionOption ? '' : $regionOption))"
                                    @class([
                                        'h-9 px-3.5 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 cursor-pointer',
                                        'bg-primary text-on-primary font-semibold' => $region === $regionOption,
                                        'bg-surface-container-lowest text-text-primary shadow-card' => $region !== $regionOption,
                                    ])>
                                <span>{{ $regionOption }}</span>
                                @if ($region === $regionOption)
                                    <x-icon name="check" size="14" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-2.5">
                    <span class="font-label-lg text-label-lg text-text-primary">{{ __('Catégories de produits') }}</span>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($this->categories() as $categoryOption)
                            <button type="button" wire:click="$set('category', '{{ $category === $categoryOption->slug ? '' : $categoryOption->slug }}')"
                                    @class([
                                        'flex items-center gap-2 p-2.5 rounded-xl text-left cursor-pointer',
                                        'bg-primary text-on-primary' => $category === $categoryOption->slug,
                                        'bg-surface-container-lowest text-text-primary shadow-card' => $category !== $categoryOption->slug,
                                    ])>
                                <x-icon :name="$category === $categoryOption->slug ? 'check_box' : 'check_box_outline_blank'" size="20" />
                                <span class="font-label-sm text-label-sm truncate">{{ $categoryOption->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-gutter pt-2 bg-surface flex items-center gap-space-sm pb-8 shadow-[0_-4px_12px_rgba(0,0,0,0.03)] shrink-0">
                <button type="button" wire:click="resetFilters" x-on:click="filters = false" data-test="reset-filters"
                        class="flex-1 h-12 rounded-full bg-surface-container text-text-primary font-body-md-bold text-body-md-bold transition-colors cursor-pointer">
                    {{ __('Réinitialiser') }}
                </button>

                <button type="button" x-on:click="filters = false"
                        class="flex-[1.5] h-12 rounded-full bg-primary text-on-primary font-body-md-bold text-body-md-bold transition-transform active:scale-95 shadow-raised cursor-pointer">
                    {{ __('Appliquer les filtres') }}
                </button>
            </div>
        </div>
    </div>
</div>
