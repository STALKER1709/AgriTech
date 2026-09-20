<div class="flex w-full flex-col gap-5 py-2">
    {{-- Screen header, as in the Stitch catalogue screen: overline, title,
         intro, search bar and pill filters on their own rows. --}}
    <section class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-stitch-primary/10 px-2.5 py-0.5 text-xs font-semibold text-stitch-primary">
                <flux:icon.map-pin class="size-3.5" />
                {{ __('Cameroun') }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 text-xs text-stitch-muted shadow-card">
                <span class="size-1.5 rounded-full bg-stitch-success"></span>
                {{ __('Circuits courts') }}
            </span>
        </div>

        <h1 class="font-display text-2xl font-bold tracking-tight text-stitch-ink sm:text-3xl">
            {{ __('Catalogue des récoltes') }}
        </h1>
        <p class="text-base text-stitch-muted">
            {{ __('Des produits frais en direct des producteurs locaux') }}
        </p>
    </section>

    {{-- Search bar --}}
    <div class="relative flex w-full items-center">
        <flux:icon.magnifying-glass class="pointer-events-none absolute left-3.5 size-5 text-stitch-muted" />
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Rechercher par produit, ferme, ville…') }}"
            class="h-12 w-full rounded-xl border border-stitch-border bg-white pl-11 pr-4 text-base text-stitch-ink shadow-card placeholder:text-stitch-muted/60 focus:border-stitch-primary focus:outline-none focus:ring-2 focus:ring-stitch-primary/25"
            data-test="search-input"
        />
    </div>

    {{-- Category and region chips: horizontally scrollable pills. --}}
    <div class="no-scrollbar -mx-4 flex items-center gap-2 overflow-x-auto px-4 py-0.5">
        <button wire:click="$set('category', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $category === ''])
                data-test="filter-all-categories">
            {{ __('Toutes') }}
        </button>

        @foreach ($this->categories() as $categoryOption)
            <button wire:click="$set('category', '{{ $categoryOption->slug }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $category === $categoryOption->slug])>
                {{ $categoryOption->name }}
            </button>
        @endforeach

        @foreach ($this->regions() as $regionOption)
            <button wire:click="$set('region', @js($regionOption))"
                    @class(['stitch-chip', 'stitch-chip-active' => $region === $regionOption])>
                <flux:icon.map-pin class="size-3.5 text-stitch-terra" />
                {{ $regionOption }}
            </button>
        @endforeach
    </div>

    {{-- Sort + reset row --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:select wire:model.live="sort" class="max-w-44" :label="__('Trier par')" data-test="sort">
            <flux:select.option value="recent">{{ __('Les plus récents') }}</flux:select.option>
            <flux:select.option value="price_asc">{{ __('Prix croissant') }}</flux:select.option>
            <flux:select.option value="price_desc">{{ __('Prix décroissant') }}</flux:select.option>
            <flux:select.option value="name">{{ __('Nom (A→Z)') }}</flux:select.option>
        </flux:select>

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-stitch-ink">
                {{ trans_choice('{0}Aucun produit|{1}:count produit|[2,*]:count produits disponibles', $products->total(), ['count' => $products->total()]) }}
            </span>

            @if ($search !== '' || $category !== '' || $region !== '')
                <flux:button size="sm" variant="ghost" wire:click="resetFilters" class="rounded-full">
                    {{ __('Réinitialiser') }}
                </flux:button>
            @endif
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="stitch-card flex flex-col items-center gap-2 p-8 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low text-stitch-muted">
                <flux:icon.magnifying-glass class="size-6" />
            </span>
            <flux:heading size="sm">{{ __('Aucun produit ne correspond') }}</flux:heading>
            <flux:text class="max-w-xs text-sm">
                {{ __('Essayez d\'élargir votre recherche ou de retirer un filtre.') }}
            </flux:text>
        </div>
    @else
        {{-- Two-column grid on mobile, as drawn in the Stitch screen. --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate
                   class="stitch-card group flex flex-col overflow-hidden p-2.5 transition-shadow hover:shadow-raised"
                   data-test="product-card">
                    <div class="relative aspect-square w-full overflow-hidden rounded-lg bg-stitch-container">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                 loading="lazy"
                                 class="size-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        @else
                            <div class="flex size-full items-center justify-center">
                                <flux:icon.photo class="size-8 text-stitch-highest" />
                            </div>
                        @endif

                        <span class="stitch-badge-success absolute left-2 top-2 text-[10px] shadow-sm">
                            <span class="size-1.5 rounded-full bg-stitch-success"></span>
                            {{ $product->category->name }}
                        </span>
                    </div>

                    <div class="mt-2 flex min-w-0 flex-col">
                        <span class="flex items-center gap-1 text-xs text-stitch-muted">
                            <flux:icon.map-pin class="size-3 shrink-0" />
                            <span class="truncate">{{ $product->farmer->farmerProfile?->region }}</span>
                        </span>
                        <flux:heading size="sm" class="mt-0.5 line-clamp-2 text-[15px] leading-tight">
                            {{ $product->name }}
                        </flux:heading>
                        <span class="truncate text-xs text-stitch-muted/90">
                            {{ $product->farmer->farmerProfile?->farm_name }}
                        </span>
                    </div>

                    <div class="mt-1 flex items-end justify-between gap-2 pb-0.5 pt-2">
                        <div class="flex min-w-0 flex-col">
                            <span class="stitch-price text-base leading-tight">
                                {{ $product->unit_price->format() }}
                            </span>
                            <span class="text-[11px] text-stitch-muted">
                                / {{ $product->unit->shortLabel() }}
                            </span>
                        </div>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary transition-colors group-hover:bg-stitch-primary group-hover:text-white">
                            <flux:icon.plus class="size-4" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
</div>
