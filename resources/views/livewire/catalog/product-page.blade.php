<div class="flex w-full flex-col gap-5 py-2">
    {{-- Breadcrumb / back row, like the Stitch product screen. --}}
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('catalog.browse') }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-full py-1.5 text-sm text-stitch-muted transition-colors hover:text-stitch-primary">
            <flux:icon.arrow-left class="size-4" />
            {{ __('Retour au catalogue') }}
        </a>

        <span class="text-xs text-stitch-muted">
            {{ $product->category->name }}
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
        {{-- Gallery --}}
        <div class="flex flex-col gap-2.5">
            <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-stitch-container shadow-card">
                @if ($product->images->isNotEmpty())
                    <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="size-full object-cover" />
                @else
                    <div class="flex size-full items-center justify-center">
                        <flux:icon.photo class="size-10 text-stitch-highest" />
                    </div>
                @endif

                {{-- Stock badge overlaid on the image, as in the design. --}}
                @if ($product->isInStock())
                    <span class="stitch-badge-success absolute left-3 top-3 shadow-sm">
                        <span class="size-1.5 animate-pulse rounded-full bg-stitch-success"></span>
                        {{ __('En stock : :quantity :unit', [
                            'quantity' => $product->stock_quantity->format(),
                            'unit' => $product->unit->shortLabel(),
                        ]) }}
                    </span>
                @else
                    <span class="stitch-badge-danger absolute left-3 top-3 shadow-sm">
                        {{ __('Rupture de stock') }}
                    </span>
                @endif
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                    @foreach ($product->images->skip(1) as $image)
                        <div class="aspect-square w-full overflow-hidden rounded-xl bg-stitch-container">
                            <img src="{{ $image->url() }}" alt="" loading="lazy" class="size-full object-cover" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Information column --}}
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="font-display text-2xl font-bold tracking-tight text-stitch-ink sm:text-3xl">
                    {{ $product->name }}
                </h1>

                {{-- Price block on its warm pill card. --}}
                <div class="mt-2 flex items-baseline justify-between gap-3 rounded-2xl bg-stitch-low p-3">
                    <div class="flex flex-wrap items-baseline gap-1.5">
                        <span class="stitch-price text-2xl sm:text-3xl" data-test="product-price">
                            {{ $product->unit_price->format() }}
                        </span>
                        <span class="text-base text-stitch-muted">/ {{ $product->unit->label() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-stitch-primary/10 px-2.5 py-0.5 text-xs font-semibold text-stitch-primary">
                        {{ $product->category->name }}
                    </span>
                </div>
            </div>

            {{-- Producer card --}}
            <div class="rounded-2xl bg-stitch-low p-4 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                        <flux:icon.leaf class="size-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <flux:heading size="sm" class="truncate">
                                {{ $product->farmer->farmerProfile?->farm_name }}
                            </flux:heading>
                            <flux:icon.shield-check class="size-4 shrink-0 text-stitch-primary" />
                        </div>
                        <p class="flex items-center gap-1 truncate text-xs text-stitch-muted">
                            <flux:icon.map-pin class="size-3.5 shrink-0" />
                            {{ $product->farmer->farmerProfile?->city }}, {{ $product->farmer->farmerProfile?->region }}
                        </p>
                    </div>
                </div>

                @if ($this->canAddToCart())
                    <button type="button" wire:click="contactFarmer"
                            class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-full bg-white text-sm font-semibold text-stitch-terra shadow-card transition-colors hover:bg-stitch-terra-soft/50">
                        <flux:icon.chat-bubble-left-right class="size-4" />
                        {{ __('Contacter l\'agriculteur') }}
                    </button>
                @endif
            </div>

            <div>
                <flux:heading size="sm">{{ __('Description') }}</flux:heading>
                <flux:text class="mt-2 whitespace-pre-line leading-relaxed">{{ $product->description }}</flux:text>
            </div>

            <flux:separator />

            @if (! $product->isInStock())
                <flux:callout icon="x-circle" variant="warning">
                    <flux:callout.text>
                        {{ __('Ce produit est momentanément épuisé. Revenez bientôt ou contactez l\'agriculteur.') }}
                    </flux:callout.text>
                </flux:callout>
            @elseif ($this->isVisitor())
                <div class="rounded-2xl bg-stitch-low p-4 text-center">
                    <flux:text class="text-sm">
                        {{ __('Connectez-vous avec un compte client pour ajouter ce produit à votre panier.') }}
                    </flux:text>
                    <flux:button size="sm" variant="primary" wire:click="addToCart" class="mt-3 w-full rounded-full" data-test="add-to-cart">
                        {{ __('Se connecter pour commander') }}
                    </flux:button>
                </div>
            @elseif ($this->canAddToCart())
                {{-- Quantity card with the visible add button, Stitch style. --}}
                <form wire:submit="addToCart" class="flex flex-col gap-3 rounded-2xl border border-stitch-border bg-white p-4 shadow-card">
                    <flux:input
                        wire:model="quantity"
                        :label="__('Quantité (:unit)', ['unit' => $product->unit->shortLabel()])"
                        type="text"
                        inputmode="decimal"
                        data-test="quantity" />

                    <button type="submit"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-stitch-primary text-base font-bold text-white shadow-raised transition-colors hover:bg-stitch-primary-dark active:scale-[0.99]"
                            data-test="add-to-cart">
                        <flux:icon.shopping-cart class="size-5" />
                        {{ __('Ajouter au panier') }}
                    </button>
                </form>
            @else
                <flux:callout icon="information-circle">
                    <flux:callout.text>
                        {{ __('Seul un compte client actif peut commander sur la plateforme.') }}
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    </div>
</div>
