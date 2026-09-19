<div class="flex w-full flex-col gap-6 py-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Catalogue') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Les produits de nos agriculteurs, vendus en direct.') }}</flux:text>
    </div>

    <div class="flex flex-col gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('Rechercher un produit')"
            icon="magnifying-glass"
        />

        <div class="grid gap-3 sm:grid-cols-3">
            <flux:select wire:model.live="category">
                <flux:select.option value="">{{ __('Toutes les catégories') }}</flux:select.option>
                @foreach ($this->categories() as $categoryOption)
                    <flux:select.option value="{{ $categoryOption->slug }}">{{ $categoryOption->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="region">
                <flux:select.option value="">{{ __('Toutes les régions') }}</flux:select.option>
                @foreach ($this->regions() as $regionOption)
                    <flux:select.option value="{{ $regionOption }}">{{ $regionOption }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sort">
                <flux:select.option value="recent">{{ __('Les plus récents') }}</flux:select.option>
                <flux:select.option value="price_asc">{{ __('Prix croissant') }}</flux:select.option>
                <flux:select.option value="price_desc">{{ __('Prix décroissant') }}</flux:select.option>
                <flux:select.option value="name">{{ __('Nom (A→Z)') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="flex items-center justify-between gap-2">
        <flux:text class="text-sm">
            {{ trans_choice('{0}Aucun produit|{1}:count produit|[2,*]:count produits', $products->total(), ['count' => $products->total()]) }}
        </flux:text>

        @if ($search !== '' || $category !== '' || $region !== '')
            <flux:button size="sm" variant="ghost" wire:click="resetFilters">{{ __('Réinitialiser') }}</flux:button>
        @endif
    </div>

    @if ($products->isEmpty())
        <flux:callout icon="magnifying-glass">
            <flux:callout.heading>{{ __('Aucun produit ne correspond') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Essayez d\'élargir votre recherche ou de retirer un filtre.') }}</flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate
                   class="flex flex-col overflow-hidden rounded-xl border border-neutral-200 transition hover:border-neutral-400 dark:border-neutral-700">
                    <div class="aspect-4/3 w-full bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                 loading="lazy" class="size-full object-cover" />
                        @else
                            <div class="flex size-full items-center justify-center">
                                <flux:icon.photo class="size-8 text-zinc-400" />
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-1 p-3">
                        <flux:badge size="sm" class="self-start">{{ $product->category->name }}</flux:badge>
                        <flux:heading size="sm" class="mt-1">{{ $product->name }}</flux:heading>
                        <flux:text class="text-xs">
                            {{ $product->farmer->farmerProfile?->farm_name }}
                            @if ($product->farmer->farmerProfile?->region)
                                · {{ $product->farmer->farmerProfile->region }}
                            @endif
                        </flux:text>
                        <flux:heading class="mt-auto pt-2">
                            {{ $product->unit_price->format() }}
                            <span class="text-sm font-normal">/ {{ $product->unit->shortLabel() }}</span>
                        </flux:heading>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
</div>
