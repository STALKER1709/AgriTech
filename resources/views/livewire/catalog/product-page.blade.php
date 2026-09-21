<div class="flex w-full flex-col gap-5 py-2">
    {{-- Back breadcrumb, exactly like the Stitch fiche_produit screen. --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('catalog.browse') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-stitch-muted transition-colors hover:text-stitch-primary">
            <flux:icon.arrow-left class="size-4" />
            {{ __('Retour au catalogue') }}
        </a>
        <span class="hidden text-xs text-stitch-muted sm:inline">
            {{ $product->category->name }} <span aria-hidden>·</span> {{ $product->name }}
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

                {{-- Freshness badge overlaid, as in the design. --}}
                @if ($product->isInStock())
                    <span class="stitch-badge-success absolute left-3 top-3 shadow-sm">
                        {{ __('Frais du jour') }}
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
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-stitch-primary/10 px-2.5 py-0.5 text-xs font-semibold text-stitch-primary">
                        {{ $product->category->name }}
                    </span>
                    {{-- Stock line from the design (« 120 sacs disponibles en stock »). --}}
                    <span class="text-xs text-stitch-muted">
                        @if ($product->isInStock())
                            {{ __(':quantity :unit disponibles', [
                                'quantity' => $product->stock_quantity->format(),
                                'unit' => $product->unit->shortLabel(),
                            ]) }}
                        @else
                            {{ __('Momentanément indisponible') }}
                        @endif
                    </span>
                </div>

                <h1 class="font-display text-2xl font-bold tracking-tight text-stitch-ink sm:text-3xl">
                    {{ $product->name }}
                </h1>

                {{-- Price block on its warm pill card, with unit price detail. --}}
                <div class="mt-2 flex items-baseline justify-between gap-3 rounded-2xl bg-stitch-low p-3">
                    <div class="flex flex-wrap items-baseline gap-1.5">
                        <span class="stitch-price text-2xl sm:text-3xl" data-test="product-price">
                            {{ $product->unit_price->format() }}
                        </span>
                        <span class="text-base text-stitch-muted">/ {{ $product->unit->shortLabel() }}</span>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-stitch-primary shadow-card">
                        <flux:icon.shield-check class="size-3.5" />
                        {{ __('Producteur vérifié') }}
                    </span>
                </div>
            </div>

            {{-- Producer card, faithful to the design's producer block. --}}
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
                            {{ __('Terroir :region — :city', [
                                'region' => $product->farmer->farmerProfile?->region ?? __('Cameroun'),
                                'city' => $product->farmer->farmerProfile?->city ?? '—',
                            ]) }}
                        </p>
                    </div>
                </div>

                @if ($this->canAddToCart())
                    <button type="button" wire:click="contactFarmer"
                            class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-full bg-white text-sm font-semibold text-stitch-terra shadow-card transition-colors hover:bg-stitch-terra-soft/50">
                        <flux:icon.chat-bubble-left-right class="size-4" />
                        {{ __('Contacter le producteur') }}
                    </button>
                @endif
            </div>

            <div>
                <flux:heading size="sm">{{ __('Description du produit') }}</flux:heading>
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
                {{-- Quantity stepper with − / + round controls, per the design
                     (minimum 44px touch targets). The numeric field stays a
                     free decimal input wired to the same `quantity` state. --}}
                <form wire:submit="addToCart" class="flex flex-col gap-3 rounded-2xl border border-stitch-border bg-white p-4 shadow-card">
                    <div>
                        <flux:label>{{ __('Quantité souhaitée') }}</flux:label>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="flex h-12 flex-1 items-center overflow-hidden rounded-full border border-stitch-border bg-white shadow-card">
                                <button type="button"
                                        x-data @click="
                                            const input = $el.parentElement.querySelector('input');
                                            input.value = Math.max(1, (parseFloat(String(input.value).replace(',', '.')) || 1) - 1);
                                            input.dispatchEvent(new Event('input'));
                                        "
                                        class="grid h-full w-12 place-items-center text-lg text-stitch-muted transition hover:text-stitch-primary"
                                        aria-label="{{ __('Diminuer la quantité') }}">−</button>

                                <flux:input
                                    wire:model="quantity"
                                    type="text"
                                    inputmode="decimal"
                                    class="flex-1 [&_input]:!rounded-none [&_input]:!border-x [&_input]:!border-stitch-border [&_input]:text-center [&_input]:font-bold"
                                    data-test="quantity" />

                                <button type="button"
                                        x-data @click="
                                            const input = $el.parentElement.querySelector('input');
                                            input.value = (parseFloat(String(input.value).replace(',', '.')) || 0) + 1;
                                            input.dispatchEvent(new Event('input'));
                                        "
                                        class="grid h-full w-12 place-items-center text-lg text-stitch-muted transition hover:text-stitch-primary"
                                        aria-label="{{ __('Augmenter la quantité') }}">+</button>
                            </div>

                            <span class="shrink-0 rounded-full bg-stitch-low px-3 py-2 text-xs font-semibold text-stitch-muted">
                                {{ $product->unit->shortLabel() }}
                            </span>
                        </div>
                    </div>

                    {{-- Estimated total line, mirroring the « Total estimé » bar. --}}
                    <div class="flex items-center justify-between rounded-xl bg-stitch-low px-3.5 py-2.5">
                        <span class="text-xs font-semibold text-stitch-muted">{{ __('Total estimé') }}</span>
                        <span class="stitch-price text-lg">
                            {{ \App\Support\Money::fromInteger((int) round($product->unit_price->amount * max(0, (float) str_replace(',', '.', $this->quantity))))->format() }}
                        </span>
                    </div>

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

    {{-- From the same producer: horizontal shelf of the farmer's other items. --}}
    @php($sameFarmer = $product->farmer->products()
        ->whereKeyNot($product->id)
        ->visibleToPublic()
        ->with(['images', 'category'])
        ->latest()
        ->limit(4)
        ->get())
    @if ($sameFarmer->isNotEmpty())
        <section class="flex flex-col gap-3 pt-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-base font-bold">{{ __('Du même producteur') }}</h2>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($sameFarmer as $other)
                    <a href="{{ route('catalog.product', ['product' => $other->slug]) }}" wire:navigate
                       class="stitch-card group flex flex-col overflow-hidden p-2.5 transition-shadow hover:shadow-raised">
                        <div class="relative aspect-square w-full overflow-hidden rounded-lg bg-stitch-container">
                            @if ($other->images->isNotEmpty())
                                <img src="{{ $other->images->first()->url() }}" alt="{{ $other->name }}"
                                     loading="lazy"
                                     class="size-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            @endif
                        </div>
                        <span class="mt-1.5 truncate px-0.5 text-xs text-stitch-muted">{{ $other->category->name }}</span>
                        <span class="truncate px-0.5 text-sm font-semibold">{{ $other->name }}</span>
                        <span class="stitch-price px-0.5 pt-1 text-sm">{{ $other->unit_price->format() }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
